<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Facades\DB; 

class SubmitInvoicesBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $microChunks;
    public $consolidateStatus;
    public $connectionIntegrate;

    public $timeout = 300; 
    public $tries = 5;     

    public function __construct(array $microChunks, $consolidateStatus, $connectionIntegrate)
    {
        $this->microChunks = $microChunks;
        $this->consolidateStatus = $consolidateStatus;
        $this->connectionIntegrate = $connectionIntegrate;
    }

    public function handle()
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $limitKey = 'lhdn_api_submissions';

        if (RateLimiter::tooManyAttempts($limitKey, 30)) {
            $secondsToWait = RateLimiter::availableIn($limitKey);
            Log::info("LHDN Traffic control active. Delaying job for {$secondsToWait} seconds.");
            return $this->release($secondsToWait);
        }

        RateLimiter::hit($limitKey, 60);

        Session::put('consolidate_status', $this->consolidateStatus);
        Session::put('connection_integrate', $this->connectionIntegrate);

        $model = new eInvoisModel();
        $model->consolidate_status = $this->consolidateStatus; 
        
        $invoiceIds = collect($this->microChunks)->flatten()->toArray();

        if (empty($invoiceIds)) {
            return;
        }
        
        try {
            $model->submitBatch($invoiceIds);

        } catch (\Throwable $e) { 
            
            $errorCode = (string)$e->getCode();
            $rawError = $e->getMessage();
            $rawResponseJson = null;

            // 🚀 1. DEEP JSON EXTRACTION: Digs into LHDN's exact validation rules
            if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                try {
                    $rawResponseJson = $e->getResponse()->getBody()->getContents();
                    $decoded = json_decode($rawResponseJson, true);

                    if ($decoded) {
                        // Capture the specific LHDN validation error and field target
                        if (isset($decoded['error']['details']) && is_array($decoded['error']['details'])) {
                            $errs = [];
                            foreach ($decoded['error']['details'] as $detail) {
                                $m = $detail['message'] ?? '';
                                $t = $detail['target'] ?? '';
                                if ($m) $errs[] = $t ? "$m (Field: $t)" : $m;
                            }
                            if (!empty($errs)) $rawError = implode(" | ", $errs);
                        } elseif (isset($decoded['error']['message'])) {
                            $rawError = $decoded['error']['message'];
                        } elseif (isset($decoded['message'])) {
                            $rawError = $decoded['message'];
                        }
                    }
                } catch (\Exception $parseEx) {
                    // Fallback to initial message
                }
            }

            // 🚀 2. CLIENT TRANSLATOR
            $clientFriendlyMsg = match (true) {
                str_contains($rawError, 'Undefined array key') => "System Error: A required data column is missing.",
                str_contains($rawError, 'Attempt to read property') => "System Error: Invoice structure is corrupted.",
                str_contains($errorCode, '401') => "Unauthorized: Your LHDN Digital Signature is invalid or expired.",
                str_contains($errorCode, '403') => "Forbidden: Check your LHDN permissions.",
                default => $rawError // Outputs the specific LHDN validation array extracted above
            };

            if (str_contains($errorCode, '429') || str_contains($rawError, '429') || str_contains(strtolower($rawError), 'rate limit')) {
                preg_match('/in (\d+) seconds/', $rawError, $matches);
                $retryAfter = isset($matches[1]) ? (int)$matches[1] : 60; 
                Log::warning("LHDN Rate Limit hit. Retrying job in {$retryAfter} seconds.");
                return $this->release($retryAfter); 
            }

            // 🚀 3. UPDATE DB & FREEZE INVOICES
            DB::table('invoice')
                ->whereIn('id_invoice', $invoiceIds)
                ->update([
                    'submission_status' => 'Failed',
                    'is_processing' => 0, // Unlocks for the sweeper to ignore
                    'is_failed' => 1,
                    'updated_at' => now()
                ]);

            // 🚀 4. WRITE EXACT ERROR TO DB FOR NOTIFICATION
            foreach ($invoiceIds as $id) {
                DB::table('message_header')->updateOrInsert(
                    ['id_invoice' => $id],
                    [
                        'status_submission' => 'ERROR',
                        'error_message'     => mb_substr($clientFriendlyMsg, 0, 500),
                        'response_json'     => $rawResponseJson ?: json_encode(['error' => $rawError]),
                        'updated_at'        => now()
                    ]
                );
            }

            // PULL EMERGENCY BRAKE
            if ($this->batch()) {
                Cache::put('batch_error_' . $this->batch()->id, $clientFriendlyMsg, 3600); 
                $this->batch()->cancel();
            }

            Log::error("Job Failed (Invoices " . implode(',', $invoiceIds) . "): " . $rawError);
            $this->fail($e); 
        }
    }
}