<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\eInvoisModel; // Explicitly import the model

class IntegrationInvoiceController2 extends Controller
{
    /**
     * Store invoice from MySyncTax integration (with tax + customer output)
     */
    public function invoice(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');

        $blockedTin = [
            'EI00000000010',
            'EI00000000020',
            'EI00000000030',
            'EI00000000040'
        ];

        if (in_array($tin, $blockedTin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This TIN No. is not allowed for Normal Invoice'
            ], 422);
        }

        $model = new eInvoisModel();
        $result = $model->processInvoice($payload, "NORMAL");
    
        return response()->json($result, 201);
    }

    public function invoice_general(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');

        $allowedTin = [
            'EI00000000010',
            'EI00000000020',
            'EI00000000030',
            'EI00000000040'
        ];

        if (!in_array($tin, $allowedTin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'TIN No. is not valid for General Invoice'
            ], 422);
        }

        $model = new eInvoisModel();
        $result = $model->processInvoice($payload, "GENERAL");
    
        return response()->json($result, 201);
    }

    public function invoice_general_note(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $model = new eInvoisModel();
        $result = $model->processNote($payload, "general");
    
        return response()->json($result, 201);
    }

    public function invoice_general_selfbill(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $model = new eInvoisModel();
        $result = $model->processNote($payload, "selfbill");
    
        return response()->json($result, 201);
    }


    public function note(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $model = new eInvoisModel();
        return $model->processNote($payload, 'normal');
    }

    public function selfBillNote(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $model = new eInvoisModel();
        return $model->processNote($payload, 'selfbill');
    }

    public function generalselfBillNote(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $model = new eInvoisModel();
        return $model->processNote($payload, 'selfbill_general');
    }

    /**
     * Cancel Document (FINAL ROBUST VERSION)
     * 1. Accepts unique_id from URL.
     * 2. Finds invoice and gets UUID (accepts 26-char IDs).
     * 3. Catches specific exceptions (72-hour limit, Already Cancelled).
     */
    public function cancelDocument(Request $request, $unique_id)
    {
        // 1. Find the Invoice using unique_id
        $invoice = DB::table('invoice')->where('unique_id', $unique_id)->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice record not found in system.'], 404);
        }

        // 2. Get UUID (Strictly from the 'uuid' column)
        // We trim whitespace but allow 26-char IDs (no length check).
        $lhdnUuid = trim($invoice->uuid ?? '');

        if (empty($lhdnUuid)) {
            return response()->json([
                'success' => false, 
                'message' => 'UUID column is empty. Has this invoice been submitted?',
            ], 400);
        }

        // 3. Prepare Reason (Safe Input Reading)
        $reason = $request->input('reason', 'Wrong invoice details'); 

        $model = new eInvoisModel();

        try {
            // 4. Call API
            // Note: Relies on 'web' middleware in api.php to handle Auth Token.
            $response = $model->cancelDocument($lhdnUuid, $reason);

            // 5. Handle SDK Errors (if it returns array instead of throwing)
            if (is_array($response) && isset($response['error'])) {
                $errorMsg = $response['error']['message'] ?? 'Cancellation failed';
                
                // Add details
                if (isset($response['error']['details'][0]['message'])) {
                    $errorMsg .= " - " . $response['error']['details'][0]['message'];
                }

                // Check specific codes
                $code = $response['error']['code'] ?? '';
                if ($code === 'OperationPeriodOver') return response()->json(['success' => false, 'message' => 'LHDN Error: 72-hour cancellation window has expired. Please issue a Credit Note.'], 400);
                if ($code === 'DocumentStatusNotValid') return response()->json(['success' => false, 'message' => 'LHDN Error: Document is already cancelled or rejected.'], 400);

                return response()->json(['success' => false, 'message' => "LHDN Error: $errorMsg"], 400);
            }

            // 6. Success Case - Update Database
            // Resets status to Pending (NULL) so it can be deleted or re-submitted
            DB::table('invoice')
                ->where('unique_id', $unique_id)
                ->update([
                    'submission_status' => null, 
                    'uuid' => null,              
                    'long_id' => null,           
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true, 
                'message' => 'Document cancelled on LHDN and status reset to Pending.'
            ], 200);

        } catch (\Exception $e) {
            // 7. EXCEPTION HANDLING (Specific Catches)
            $realErrorMessage = $e->getMessage();
            $errorCode = '';

            // Extract from Guzzle (HTTP) Response if available
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                try {
                    $responseBody = $e->getResponse()->getBody()->getContents();
                    $jsonError = json_decode($responseBody, true);

                    if (isset($jsonError['error']['details'][0]['message'])) {
                        $realErrorMessage = $jsonError['error']['details'][0]['message'];
                        $errorCode = $jsonError['error']['details'][0]['code'] ?? '';
                    } elseif (isset($jsonError['error']['message'])) {
                        $realErrorMessage = $jsonError['error']['message'];
                        $errorCode = $jsonError['error']['code'] ?? '';
                    } elseif (isset($jsonError['message'])) {
                        $realErrorMessage = $jsonError['message'];
                    }
                } catch (\Exception $parseError) {}
            } else {
                 // Extract from JSON string exception
                 $decoded = json_decode($realErrorMessage, true);
                 if ($decoded && isset($decoded['message'])) $realErrorMessage = $decoded['message'];
            }

            // --- SPECIFIC ERROR CHECKS ---

            // Check 1: 72-Hour Limit
            if ($errorCode === 'OperationPeriodOver' || stripos($realErrorMessage, '72 hours') !== false || stripos($realErrorMessage, 'prohibited timeframe') !== false) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed: 72-hour cancellation window has expired. You must issue a Credit Note instead.'
                ], 400);
            }

            // Check 2: Document Already Cancelled / Invalid Status
            if ($errorCode === 'DocumentStatusNotValid' || stripos($realErrorMessage, 'status') !== false) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed: Document is not in "Valid" status (it may be already cancelled or rejected).'
                ], 400);
            }

            // Check 3: Bad Request Fallback
            if ($realErrorMessage === 'Bad Request') {
                $realErrorMessage = "LHDN Rejected the request. Verify the document ID ($lhdnUuid) is correct and status is 'Valid'.";
            }

            return response()->json([
                'success' => false,
                'message' => "LHDN Error: " . $realErrorMessage
            ], 400); 
        }
    }
}