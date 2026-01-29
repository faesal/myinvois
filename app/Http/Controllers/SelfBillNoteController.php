<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;

class SelfBillNoteController extends Controller
{
    // Define Self-Bill specific codes for Credit (12), Debit (13), Refund (14)
    protected $noteCodes = [
        'credit' => '12',
        'debit'  => '13',
        'refund' => '14',
    ];

    protected function getNoteTypeInfo()
    {
        $noteTypeSlug = request()->route('note_type'); 
        $noteType = str_replace('_note', '', $noteTypeSlug); 

        return [
            'type' => $noteType,
            'slug' => $noteTypeSlug,
            'code' => $this->noteCodes[$noteType] ?? '12',
        ];
    }

    public function listing(Request $request)
    {
        $search = $request->input('search');
        $info = $this->getNoteTypeInfo();

        $query = DB::table('invoice')
            ->join('customer as c', 'invoice.id_customer', '=', 'c.id_customer')
            ->join('customer as s', 'invoice.id_supplier', '=', 's.id_customer')
            ->select(
                'invoice.*',
                'c.registration_name as customer_name',
                'c.email as customer_email',
                's.registration_name as supplier_name',
                's.tin_no as supplier_tin'
            )
            ->where('invoice.invoice_type_code', $info['code']);

        if (auth()->user()->role !== 'admin') {
            $query->where('invoice.connection_integrate', session('connection_integrate'));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice.invoice_no', 'like', "%$search%")
                    ->orWhere('c.registration_name', 'like', "%$search%")
                    ->orWhere('s.registration_name', 'like', "%$search%");
            });
        }

        $notes = $query->orderBy('invoice.created_at', 'desc')->paginate(50);

        // Stats calculation - using exact string match 'Submitted'
        $totalQuery = DB::table('invoice')->where('invoice_type_code', $info['code']);
        $submittedQuery = DB::table('invoice')
            ->where('invoice_type_code', $info['code'])
            ->where('submission_status', 'Submitted');

        if (auth()->user()->role !== 'admin') {
            $totalQuery->where('connection_integrate', session('connection_integrate'));
            $submittedQuery->where('connection_integrate', session('connection_integrate'));
        }

        return view('credit_debit_notes.listing', [
            'notes' => $notes,
            'total' => $totalQuery->count(),
            'submitted' => $submittedQuery->count(),
            'search' => $search,
            'noteType' => $info['type'],
            'noteTypeSlug' => $info['slug'],
            'customCreateRoute' => route('self_bill_note.create', ['note_type' => $info['slug']])
        ]);
    }

    public function create(Request $request)
    {
        $info = $this->getNoteTypeInfo();
        $query = DB::table('invoice')->where('invoice_type_code', '11');

        if (auth()->user()->role !== 'admin') {
            $query->where('connection_integrate', session('connection_integrate'));
        }

        return view('credit_debit_notes.create', [
            'invoices' => $query->orderBy('created_at', 'desc')->get(),
            'noteType' => $info['type'],
            'noteTypeSlug' => $info['slug'],
            'customStoreRoute' => route('self_bill_note.store', ['note_type' => $info['slug']]),
            'customFetchRoute' => url('self_bill/' . $info['slug'] . '/fetchInvoiceItems'),
            'customRedirectRoute' => route('self_bill_note.listing', ['note_type' => $info['slug']])
        ]);
    }

    public function fetchInvoiceItems($note_type, $id_invoice)
    {
        $invoice = DB::table('invoice')->where('id_invoice', $id_invoice)->first();
        if (!$invoice) return response()->json(['error' => 'Invoice not found.'], 404);

        return response()->json([
            'invoice' => $invoice,
            'items' => DB::table('invoice_item')->where('id_invoice', $id_invoice)->get(),
            'customer' => DB::table('customer')->where('id_customer', $invoice->id_customer)->first(),
            'supplier' => DB::table('customer')->where('id_customer', $invoice->id_supplier)->first(),
        ]);
    }

    public function store(Request $request)
    {
        $info = $this->getNoteTypeInfo();
        $originalInvoiceId = $request->input('original_invoice_id');
        $items = $request->input('items', []);
        $unique_id = (string) Str::uuid();

        $original = DB::table('invoice')->where('id_invoice', $originalInvoiceId)->first();
        if (!$original) return response()->json(['message' => 'Original invoice not found'], 404);

        try {
            DB::beginTransaction();

            // 1. Insert Header
            $newInvoiceId = DB::table('invoice')->insertGetId([
                'unique_id' => $unique_id,
                'connection_integrate' => session('connection_integrate'),
                'invoice_no' => 'SB_' . strtoupper($info['type']) . '-' . now()->format('YmdHis'),
                'invoice_type_code' => $info['code'],
                'issue_date' => now(),
                'id_customer' => $original->id_customer,
                'id_supplier' => $original->id_supplier,
                'invoice_status' => 'Valid',
                'submission_status' => 'Pending',
                'previous_id_invoice' => $originalInvoiceId,
                'previous_invoice_no' => $original->invoice_no,
                'previous_uuid' => $original->uuid,
                'payment_note_term' => 'CASH',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Insert Items
            foreach ($items as $item) {
                if (isset($item['id_invoice_item'])) {
                    $qty = floatval($item['qty']);
                    $price = floatval($item['price']);
                    $discount = floatval($item['discount'] ?? 0);
                    $lineTotal = round(($qty * $price) - $discount, 2);

                    DB::table('invoice_item')->insert([
                        'id_invoice' => $newInvoiceId,
                        'unique_id' => $unique_id,
                        'connection_integrate' => session('connection_integrate'),
                        'previous_id_invoice' => $originalInvoiceId,
                        'previous_id_invoice_item' => $item['id_invoice_item'],
                        'line_id' => $item['id_invoice_item'],
                        'invoiced_quantity' => number_format($qty, 2, '.', ''),
                        'price_amount' => number_format($price, 2, '.', ''),
                        'tax' => number_format(floatval($item['tax'] ?? 0), 2, '.', ''),
                        'price_discount' => number_format($discount, 2, '.', ''), 
                        'line_extension_amount' => number_format($lineTotal, 2, '.', ''),
                        'price_extension_amount' => number_format($lineTotal, 2, '.', ''),
                        'item_description' => $item['description'],
                        'item_clasification_value' => $item['item_clasification_value'] ?? '022',
                        'created_at' => now(),
                    ]);
                }
            }

            // 3. Recalculate Totals
            $recalculatedTotal = DB::table('invoice_item')->where('id_invoice', $newInvoiceId)->sum('line_extension_amount');
            $taxTotal = DB::table('invoice_item')->where('id_invoice', $newInvoiceId)->sum('tax');

            DB::table('invoice')->where('id_invoice', $newInvoiceId)->update([
                'price' => number_format($recalculatedTotal + $taxTotal, 2, '.', ''),
                'taxable_amount' => number_format($recalculatedTotal, 2, '.', ''),
                'tax_amount' => number_format($taxTotal, 2, '.', ''),
            ]);

            DB::commit();

            // 4. LHDN Submission logic
            $model = new eInvoisModel(session('connection_integrate'));
            
            session([
                'invoice_unique_id' => $unique_id,
                'previous_uuid' => $original->uuid,
                'previous_invoice_no' => $original->invoice_no,
                'invoice_type_code' => $info['code']
            ]);

            $response = $model->submit($newInvoiceId);
            $result = ($response instanceof \Illuminate\Http\JsonResponse) ? $response->getData(true) : $response;

            $uuid = $result['acceptedDocuments'][0]['uuid'] ?? null;
            
            if ($uuid) {
                // Update with exact status for listing filters
                DB::table('invoice')->where('id_invoice', $newInvoiceId)->update([
                    'uuid' => $uuid,
                    'submission_uuid' => $result['submissionUid'] ?? null,
                    'submission_status' => 'Submitted',
                    'invoice_status' => 'Submitted',
                    'updated_at' => now()
                ]);
                return response()->json(['message' => ucfirst($info['type']) . ' Note submitted and UUID received.']);
            } else {
                DB::table('invoice')->where('id_invoice', $newInvoiceId)->update([
                    'submission_status' => 'Failed',
                    'updated_at' => now()
                ]);
                return response()->json(['message' => 'LHDN submission failed.', 'errors' => $result], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE Function
     */
    public function destroy($note_type, $id)
    {
        try {
            DB::beginTransaction();

            $invoice = DB::table('invoice')->where('id_invoice', $id)->first();
            
            // Safety: Don't delete if it already has a UUID from LHDN
            if ($invoice && !empty($invoice->uuid)) {
                return redirect()->back()->with('error', 'Cannot delete a note that has already been submitted to LHDN.');
            }

            DB::table('invoice_item')->where('id_invoice', $id)->delete();
            DB::table('invoice')->where('id_invoice', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Note deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}