<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Log;

class InvoiceSubmissionController extends Controller
{
    /**
     * View Invoice Submissions (Alternative View)
     */
    public function index2(Request $request)
    {
        $developerId = auth()->user()->id;

        // -------------------------
        // Filter Options (Suppliers/LHDN Accounts)
        // -------------------------
        $customers = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->orderBy('registration_name')
            ->get();

        // -------------------------
        // Invoice Query
        // -------------------------
        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
            ->leftJoin('connection_integrate AS ci', 'i.connection_integrate', '=', 'ci.code')
            ->leftJoin('invoice_item AS it', function($join) use ($developerId) {
                $join->on('it.id_invoice', '=', 'i.id_invoice')
                     ->where('it.id_developer', '=', $developerId);
            })
            ->select(
                'i.id_invoice',
                'i.invoice_no',
                'i.issue_date',
                'i.submission_status',
                'i.price',
                'c.registration_name',
                'i.id_customer',
                'i.id_supplier',
                'i.connection_integrate',
                'ci.name AS connection_name',
                DB::raw('MIN(it.sale_id_integrate) AS sale_id')
            )
            ->where('ci.id_developer', $developerId)
            ->where('c.id_developer', $developerId)
            ->where('c.customer_type', 'SUPPLIER')
            ->groupBy('i.id_invoice');

        // ----- Apply Filters -----
        if ($request->start_date) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        if ($request->status && $request->status !== 'ALL') {
            $query->where('i.submission_status', $request->status);
        }

        if ($request->connection_integrate && $request->connection_integrate !== 'ALL') {
            $query->where('i.connection_integrate', $request->connection_integrate);
            Session::put('connection_integrate', $request->connection_integrate);
        }

        $invoices = $query->orderBy('i.issue_date', 'desc')->get();

        return view('developer.invoice_submissions', compact(
            'customers',
            'invoices'
        ));
    }

    /**
     * View Invoice Submissions (Main View)
     */
    public function index(Request $request)
    {
        $developerId = auth()->user()->id;

        /*
        |--------------------------------------------------------------------------
        | Customers (SUPPLIER) for LHDN Account dropdown
        |--------------------------------------------------------------------------
        */
        $customers = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->orderBy('registration_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Invoice Types (for filter)
        |--------------------------------------------------------------------------
        */
        $invoiceTypes = DB::table('invoice_type')
            ->orderBy('code')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Invoice Query
        |--------------------------------------------------------------------------
        */
        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
            ->leftJoin('connection_integrate AS ci', 'i.connection_integrate', '=', 'ci.code')
            ->leftJoin('invoice_type AS itype', 'i.invoice_type_code', '=', 'itype.code')
            ->leftJoin('invoice_item AS it', function ($join) use ($developerId) {
                $join->on('it.id_invoice', '=', 'i.id_invoice')
                     ->where('it.id_developer', '=', $developerId);
            })
            ->select(
                'i.unique_id',
                'i.id_invoice',
                'i.invoice_no',
                'i.issue_date',
                'i.submission_status',
                'i.price',
                'i.taxable_amount',
                'i.tax_amount',
                'i.invoice_type_code',
                'itype.description AS invoice_type_name',
                'c.registration_name',
                'i.id_customer',
                'i.id_supplier',
                'i.connection_integrate',
                'ci.name AS connection_name',
                DB::raw('MIN(it.sale_id_integrate) AS sale_id')
            )
            ->where('ci.id_developer', $developerId)
            ->where('c.id_developer', $developerId)
            ->where('c.customer_type', 'SUPPLIER')
            ->groupBy('i.id_invoice');

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */
        if ($request->start_date) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        if ($request->status && $request->status !== 'ALL') {
            $query->where('i.submission_status', $request->status);
        }

        if ($request->connection_integrate && $request->connection_integrate !== 'ALL') {
            $query->where('i.connection_integrate', $request->connection_integrate);
        }

        if ($request->invoice_type && $request->invoice_type !== 'ALL') {
            $query->where('i.invoice_type_code', $request->invoice_type);
        }

        $invoices = $query
            ->orderBy('i.issue_date', 'desc')
            ->get();

        return view('developer.invoice_submissions', compact(
            'customers',
            'invoiceTypes',
            'invoices'
        ));
    }

public function consolidate(Request $request)
{
    $developerId = auth()->user()->id;

    $start = $request->input('start_date', now()->startOfMonth()->toDateString());
    $end = $request->input('end_date', now()->endOfMonth()->toDateString());

    session(['consolidate_start' => $start]);
    session(['consolidate_end' => $end]);

    $selectedConnection = $request->input('connection');

    // ---------------------------------------------------
    // Query Consolidate Invoice Items
    // ---------------------------------------------------
    $query = DB::table('consolidate_invoice_item AS cii')
        ->leftJoin('consolidate_invoice AS ci', 'cii.unique_id', '=', 'ci.unique_id')
        ->select(
            'cii.*', 
            'ci.invoice_no' 
        )
        ->whereBetween('cii.issue_date', [$start, $end]);

    // ✅ NEW FILTER: Only show items that are NOT "set" (New Data Only)
    $query->where(function ($q) {
        $q->where('cii.is_sent_invoice', 0)
          ->orWhereNull('cii.is_sent_invoice');
    });

    // ✅ STATUS CHECK: Only show items that are NOT submitted yet
    $query->whereNull('cii.submition_status');

    // Apply Connection Filters
    if ($selectedConnection) {
        $query->where('cii.connection_integrate', $selectedConnection);
    } else {
        $query->where('cii.id_developer', $developerId);
    }

    $items = $query->orderBy('cii.issue_date')->get();

    // ---------------------------------------------------
    // Available Connections for dropdown
    // ---------------------------------------------------
    $availableConnections = DB::table('customer AS c')
        ->leftJoin('connection_integrate AS ci', 'c.connection_integrate', '=', 'ci.code')
        ->select(
            'c.id_customer',
            'c.registration_name',
            'c.connection_integrate',
            'ci.name AS connection_name',
            'ci.id_connection'
        )
        ->where('c.id_developer', $developerId)
        ->where('ci.id_developer', $developerId)
        ->where('c.customer_type', 'SUPPLIER')
        ->orderBy('c.registration_name', 'ASC')
        ->get();

    return view('developer.consolidate', compact('items', 'start', 'end', 'availableConnections', 'selectedConnection'));
}

public function ConsolidateSelected(Request $request)
{
    $developerId = auth()->user()->id;
    $selectedIds = $request->input('selected_items', []);
    $selected_connection = $request->input('connection');

    if (empty($selectedIds)) {
        return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
    }

    // Fetch items
    $items = DB::table('consolidate_invoice_item')->whereIn('id_invoice_item', $selectedIds)->get();

    if ($items->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No items found.'], 400);
    }

    // Fetch supplier info
    $customer = DB::table('customer')
        ->where('connection_integrate', $selected_connection)
        ->where('customer_type', 'SUPPLIER')
        ->whereNull('deleted')
        ->first();

    if (!$customer) {
        return response()->json(['success' => false, 'message' => 'Supplier connection not found.'], 400);
    }

    $chunks = collect($items)->chunk(25);
    $invoiceBaseNo = 'CONSO-' . now()->format('Ymd-His');
    $version = 1;
    
    $totalProcessedAmount = 0; 

    foreach ($chunks as $chunk) {
        $uniqueId = (string) Str::uuid();
        $invoiceNo = $invoiceBaseNo . '-V' . $version;

        // --- CALCULATION LOGIC ---
        $taxTotal = $chunk->sum('tax');
        $subtotal = $chunk->sum(function($item) {
            return ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;
        });
        $grandTotal = $subtotal + $taxTotal;
        $totalProcessedAmount += $grandTotal;

        // 1. Create Invoice Header
        $invoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $uniqueId,
            'connection_integrate' => $selected_connection,
            'invoice_status' => 'manual',
            'id_developer' => $developerId,
            'id_customer' => 6, 
            'id_supplier' => $customer->id_customer,
            'invoice_no' => $invoiceNo,
            'invoice_type_code' => '01',
            'issue_date' => now(),
            'tax_scheme_id' => 'OTH',
            'tax_category_id'=> '01',
            'price' => $grandTotal,
            'taxable_amount' => $subtotal,
            'tax_amount' => $taxTotal,
            'payment_note_term' => 'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Invoice Items
        foreach ($chunk as $index => $item) {
            $lineNetAmount = ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;

            DB::table('invoice_item')->insert([
                'id_developer' => $developerId,
                'unique_id' => $uniqueId, 
                'id_invoice' => $invoiceId, 
                'issue_date' => $item->issue_date,
                'connection_integrate' => $item->connection_integrate,
                'sale_id_integrate' => $item->sale_id_integrate,
                'id_consolidate_invoice' => $item->id_consolidate_invoice,
                'line_id' => $index + 1,
                'id_customer' => $customer->id_customer,
                'invoiced_quantity' => $item->invoiced_quantity,
                'line_extension_amount' => $lineNetAmount, 
                'item_description' => $item->item_description,
                'price_amount' => $item->price_amount,
                'price_discount' => $item->price_discount,
                'price_extension_amount' => $item->price_extension_amount,
                'tax' => $item->tax, 
                'item_clasification_value' => '004',
                'created_at' => now(),
            ]);
        }

        // 3. Update Status (Mark as SET)
        DB::table('consolidate_invoice_item')
            ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
            ->update([
                'submition_status' => 'submitted', 
                'is_sent_invoice' => 1, // ✅ New Flag
                'updated_at' => now()
            ]);

        $version++;
    }

    $formattedTotal = number_format($totalProcessedAmount, 2);

    return response()->json([
        'success' => true, 
        'message' => "Consolidation successful! Total Amount: RM {$formattedTotal}"
    ]);
}
    public function view($id_invoice)
    {
        return "Invoice detailed page coming soon. ID: " . $id_invoice;
    }

public function showInvoice($unique_id)
    {
        // 1. Fetch invoice by Unique ID
        $invoice = DB::table('invoice')
            ->where('unique_id', $unique_id)
            ->first();

        if (!$invoice) {
            abort(404, "Invoice not found.");
        }

        // 2. Fetch Supplier (The issuer of the invoice)
        $supplier = DB::table('customer')
            ->where('id_customer', $invoice->id_supplier)
            ->first();

        // 3. Fetch Customer (The buyer)
        $customer = DB::table('customer')
            ->where('id_customer', $invoice->id_customer)
            ->first();

        // 4. Fetch items 
        // Priority: Fetch by unique_id (best for consolidated/new invoices)
        $items = DB::table('invoice_item')
            ->where('unique_id', $unique_id)
            ->get();

        // Fallback: Fetch by id_invoice (for older data support)
        if ($items->isEmpty()) {
            $items = DB::table('invoice_item')
                ->where('id_invoice', $invoice->id_invoice)
                ->get();
        }

        return view('developer.show_invoice', compact(
            'invoice', 'customer', 'supplier', 'items'
        ));
    }
    public function submitSelectedInvoices(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request type.'], 400);
        }

        $developerId = auth()->user()->id;

        // FIX: receive invoices from AJAX
        $selectedIds = $request->input('invoices', []);

        if (empty($selectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No invoices selected.'
            ], 400);
        }

        // Validate invoices belong to developer
        $invoices = DB::table('invoice')
            ->whereIn('id_invoice', $selectedIds)
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid invoices found.'
            ], 400);
        }

        // Save selected connection
        Session::put('connection_integrate', $request->connection_integrate);

        foreach ($invoices as $inv) {
            session([
                 'invoice_type_code' => $inv->invoice_type_code ,
                 'invoice_unique_id' => $inv->unique_id
             ]);

            session(['consolidate_status' => '']);

            if(empty($inv->id_customer))
                session(['consolidate_status' => '1']);

            DB::table('invoice')
                ->where('id_invoice', $inv->id_invoice)
                ->update([
                    'submission_status' => 'Submitted',
                    'updated_at' => now()
                ]);

            // your submission model
            $model = new \App\Models\eInvoisModel;
            $model->submit($inv->id_invoice);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected invoices submitted successfully.',
            'connection_integrate' => session('connection_integrate')
        ], 200);
    }

    /**
     * Delete Invoice (Only if Pending or Failed)
     */
public function deleteInvoice($id)
{
    $invoice = DB::table('invoice')->where('id_invoice', $id)->first();

    // 1. Validation
    if (!$invoice) {
        return redirect()->back()->with('error', 'Invoice not found.');
    }

    // 2. Validation
    $status = strtolower($invoice->submission_status ?? '');
    if ($status === 'submitted') {
        return redirect()->back()->with('error', 'Cannot delete an invoice that has been successfully submitted to LHDN.');
    }

    DB::beginTransaction();
    try {
        // 3. REVERSAL: Find items belonging to this invoice and reset flag to 0
        DB::table('consolidate_invoice_item')
            ->whereIn('id_consolidate_invoice', function($query) use ($id) {
                $query->select('id_consolidate_invoice')
                      ->from('invoice_item')
                      ->where('id_invoice', $id);
            })
            ->update([
                'submition_status' => null, // Clear submitted status
                'is_sent_invoice' => 0,      // ✅ Reset flag to 0 so it reappears
                'updated_at' => now()
            ]);

        // 4. Delete Invoice Data
        DB::table('invoice_item')->where('id_invoice', $id)->delete();
        DB::table('invoice')->where('id_invoice', $id)->delete();

        DB::commit();
        
        return redirect()->back()->with('success', 'Invoice deleted successfully. Items (if consolidated) are available again.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
    }
}
    /**
     * Delete a specific Consolidate Item
     */
 public function destroyConsolidateItem($id)
    {
        // 1. Find the item
        $item = DB::table('consolidate_invoice_item')
            ->where('id_invoice_item', $id)
            ->first();

        // Check if it exists at all
        if (!$item) {
            return response()->json([
                'success' => false, 
                'message' => 'Item not found.'
            ], 404);
        }

        // 2. Force Delete (Safety check removed as requested)
        DB::table('consolidate_invoice_item')->where('id_invoice_item', $id)->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Item deleted successfully.'
        ]);
    }
    public function bulkDeleteConsolidateItems(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
        }

        DB::table('consolidate_invoice_item')->whereIn('id_invoice_item', $ids)->delete();

        return response()->json(['success' => true, 'message' => count($ids) . ' items deleted successfully.']);
    }


public function autoConsolidate(Request $request)
    {
        $processedRetries = 0;
        $processedBatches = 0;

        // ====================================================================
        // PHASE 1: RESUBMIT FAILED INVOICES (Background Sweep)
        // ====================================================================
        // Grab up to 10 failed invoices per cron to prevent timeout
        $failedInvoices = null;
        
        \DB::transaction(function () use (&$failedInvoices) {
            $failedInvoices = \DB::table('invoice')
                ->where('is_failed', 1)
                ->where('submission_status', 'Failed')
                ->limit(10)
                ->lockForUpdate() // Lock these so other crons don't double-submit
                ->get();

            if ($failedInvoices->isNotEmpty()) {
                $ids = $failedInvoices->pluck('id_invoice')->toArray();
                \DB::table('invoice')
                    ->whereIn('id_invoice', $ids)
                    ->update(['submission_status' => 'Retrying', 'updated_at' => now()]);
            }
        });

        if ($failedInvoices && $failedInvoices->isNotEmpty()) {
            foreach ($failedInvoices as $record) {
                try {
                    // Identify Invoice Category (Self-Bill vs Normal)
                    $isSelfBill = in_array($record->invoice_type_code, ['11', '12', '13', '14']);
                    
                    // Clear previous session safely
                    session()->forget([
                        'invoice_unique_id', 'selfbill_unique_id', 'previous_uuid', 
                        'previous_invoice_no', 'invoice_type_code', 'is_selfbilled', 'consolidate_status'
                    ]);

                    \Session::put('connection_integrate', $record->connection_integrate);

                    // Set precise session based on your autoResubmit logic
                    if ($isSelfBill) {
                        session([
                            'selfbill_unique_id' => $record->unique_id,
                            'invoice_type_code'  => $record->invoice_type_code,
                            'is_selfbilled'      => true
                        ]);
                    } else {
                        session([
                            'invoice_unique_id'   => $record->unique_id,
                            'previous_uuid'       => $record->previous_uuid,
                            'previous_invoice_no' => $record->previous_invoice_no,
                            'invoice_type_code'   => $record->invoice_type_code
                        ]);
                    }

                    // Dummy Consolidate Logic
                    if (empty($record->id_customer) || $record->id_customer == 6) {
                        session(['consolidate_status' => '1']);
                    }

                    $model = new \App\Models\eInvoisModel($record->connection_integrate);
                    $model->submit($record->id_invoice);

                    // SUCCESS: Reset the failed flag
                    \DB::table('invoice')->where('id_invoice', $record->id_invoice)->update([
                        'is_failed' => 0, 
                        'submission_status' => 'Submitted',
                        'updated_at' => now()
                    ]);
                    $processedRetries++;

                } catch (\Exception $e) {
                    \Log::error("Auto-Retry Failed for Inv #{$record->invoice_no}: " . $e->getMessage());
                    
                    // FAILURE: Revert to Failed so it tries again infinitely on next cron runs
                    \DB::table('invoice')->where('id_invoice', $record->id_invoice)->update([
                        'is_failed' => 1, 
                        'submission_status' => 'Failed',
                        'updated_at' => now()
                    ]);
                }
            }
        }

        // ====================================================================
        // PHASE 2: CONSOLIDATE NEW ITEMS (Scheduled Queue)
        // ====================================================================
        $candidates = \DB::table('consolidate_setting')
            ->join('customer', 'consolidate_setting.connection_integrate', '=', 'customer.connection_integrate')
            ->where('consolidate_setting.is_enabled', 1)
            ->where('consolidate_setting.next_consolidate', '<=', now())
            ->select('customer.id_developer', 'customer.connection_integrate')
            ->distinct()
            ->get();

        if ($candidates->isEmpty() && $processedRetries === 0) {
            return response()->json(['success' => true, 'message' => 'No scheduled consolidations or retries due.']);
        }

        // 250 items / 5 items per invoice = 50 Invoices handled per Cron (+10 Retries)
        // This guarantees the cron finishes safely under the 60-second limit.
        $batchSize = 250; 
        $itemsPerInvoice = 5;

        foreach ($candidates as $candidate) {
            $developerId = $candidate->id_developer;
            $connection = $candidate->connection_integrate;
            
            $itemsToProcess = null;

            \DB::transaction(function () use ($developerId, $batchSize, &$itemsToProcess) {
                $items = \DB::table('consolidate_invoice_item')
                    ->where('id_developer', $developerId)
                    ->where(function ($q) {
                        $q->whereNull('submition_status')->orWhere('submition_status', 'pending');
                    })
                    ->where(function ($q) {
                        $q->where('is_sent_invoice', 0)->orWhereNull('is_sent_invoice');
                    })
                    ->orderBy('issue_date', 'asc')
                    ->limit($batchSize)
                    ->lockForUpdate() 
                    ->get();

                if ($items->isNotEmpty()) {
                    $itemIds = $items->pluck('id_invoice_item')->toArray();

                    \DB::table('consolidate_invoice_item')
                        ->whereIn('id_invoice_item', $itemIds)
                        ->update(['submition_status' => 'processing', 'updated_at' => now()]);
                    
                    $itemsToProcess = $items;
                }
            });

            if (empty($itemsToProcess)) {
                $this->checkAndFinalize($developerId, $connection);
                continue;
            }

            try {
                $supplier = \DB::table('customer')
                    ->where('id_developer', $developerId)
                    ->where('customer_type', 'SUPPLIER')
                    ->whereNull('deleted')
                    ->first();

                if ($supplier) {
                    \Session::put('connection_integrate', $connection);
                    
                    $chunks = $itemsToProcess->chunk($itemsPerInvoice); 
                    $invoiceBaseNo = 'AUTO-' . now()->format('Ymd-His');
                    
                    $processedItemIds = [];
                    $invoiceTypeCode = ($supplier->is_selfbill_supplier == 1) ? '11' : '01';

                    foreach ($chunks as $chunk) {
                        $uniqueId = (string) \Str::uuid();
                        $invoiceNo = $invoiceBaseNo . '-' . strtoupper(\Str::random(4));

                        $taxTotal = $chunk->sum('tax');
                        $subtotal = $chunk->sum(function($item) {
                            return ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;
                        });
                        $grandTotal = $subtotal + $taxTotal;

                        // Insert Header (Fixing 0.00 issue)
                        $invoiceId = \DB::table('invoice')->insertGetId([
                            'unique_id' => $uniqueId,
                            'connection_integrate' => $connection,
                            'invoice_status' => 'manual',
                            'submission_status' => 'Pending',
                            'is_failed' => 0,
                            'id_developer' => $developerId,
                            'id_customer' => 6, 
                            'id_supplier' => $supplier->id_customer,
                            'invoice_no' => $invoiceNo,
                            'invoice_type_code' => $invoiceTypeCode,
                            'issue_date' => now(),
                            'tax_scheme_id' => 'OTH',
                            'tax_category_id'=> '01',
                            'price' => number_format((float)$grandTotal, 2, '.', ''),
                            'taxable_amount' => number_format((float)$subtotal, 2, '.', ''),
                            'tax_amount' => number_format((float)$taxTotal, 2, '.', ''),
                            'payment_note_term' => 'CASH',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($chunk as $index => $item) {
                            $lineNetAmount = ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;
                            \DB::table('invoice_item')->insert([
                                'id_developer' => $item->id_developer,
                                'unique_id' => $uniqueId, 
                                'id_invoice' => $invoiceId, 
                                'issue_date' => $item->issue_date,
                                'connection_integrate' => $connection, 
                                'sale_id_integrate' => $item->sale_id_integrate,
                                'id_consolidate_invoice' => $item->id_consolidate_invoice,
                                'line_id' => $index + 1,
                                'id_customer' => $supplier->id_customer,
                                'invoiced_quantity' => $item->invoiced_quantity,
                                'line_extension_amount' => number_format((float)$lineNetAmount, 2, '.', ''), 
                                'item_description' => $item->item_description,
                                'price_amount' => number_format((float)$item->price_amount, 2, '.', ''),
                                'price_discount' => number_format((float)$item->price_discount, 2, '.', ''),
                                'price_extension_amount' => number_format((float)$item->price_extension_amount, 2, '.', ''),
                                'tax' => number_format((float)$item->tax, 2, '.', ''), 
                                'item_clasification_value' => '004',
                                'created_at' => now(),
                            ]);
                            $processedItemIds[] = $item->id_invoice_item;
                        }

                        // Submit to LHDN
                        try {
                            session()->forget(['invoice_unique_id', 'selfbill_unique_id', 'invoice_type_code', 'is_selfbilled']);
                            session(['consolidate_status' => '1']); 
                            
                            if (in_array($invoiceTypeCode, ['11', '12', '13', '14'])) {
                                session(['selfbill_unique_id' => $uniqueId, 'invoice_type_code' => $invoiceTypeCode, 'is_selfbilled' => true]);
                            } else {
                                session(['invoice_unique_id' => $uniqueId, 'invoice_type_code' => $invoiceTypeCode]);
                            }
                            
                            $model = new \App\Models\eInvoisModel($connection);
                            $model->submit($invoiceId);

                            \DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                                'submission_status' => 'Submitted',
                                'updated_at' => now()
                            ]);
                        } catch (\Exception $e) {
                            \Log::error("Failed to submit Auto-Consolidate Inv #{$invoiceNo}: " . $e->getMessage());
                            
                            // IF NEW INVOICE FAILS: Mark is_failed = 1 so the retry loop above grabs it next time!
                            \DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                                'submission_status' => 'Failed',
                                'is_failed' => 1,
                                'updated_at' => now()
                            ]);
                        }
                    }

                    if (!empty($processedItemIds)) {
                        \DB::table('consolidate_invoice_item')
                            ->whereIn('id_invoice_item', $processedItemIds)
                            ->update(['submition_status' => 'submitted', 'is_sent_invoice' => 1, 'updated_at' => now()]);
                    }
                }
                
                $processedBatches++;
                $this->checkAndFinalize($developerId, $connection);

            } catch (\Exception $e) {
                \Log::error("Consolidate Batch Error ($developerId): " . $e->getMessage());
                if (!empty($itemsToProcess)) {
                    $itemIds = $itemsToProcess->pluck('id_invoice_item')->toArray();
                    \DB::table('consolidate_invoice_item')
                        ->whereIn('id_invoice_item', $itemIds)
                        ->update(['submition_status' => null]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cycle complete. Retried {$processedRetries} failed invoices. Processed {$processedBatches} new batches."
        ]);
    }

    // Helper to Check Remaining Items
    private function checkAndFinalize($developerId, $connection) {
        $remainingCount = \DB::table('consolidate_invoice_item')
            ->where('id_developer', $developerId)
            ->where(function($q) {
                $q->whereNull('submition_status')->orWhere('submition_status', 'pending')->orWhere('submition_status', 'processing');
            })
            ->where(function ($q) {
                $q->where('is_sent_invoice', 0)->orWhereNull('is_sent_invoice');
            })
            ->count();

        if ($remainingCount === 0) {
            $this->finalizeConsolidation($developerId, $connection);
        }
    }

    // Helper: Email and Reschedule
    private function finalizeConsolidation($developerId, $connection) {
        $supplier = \DB::table('customer')->where('id_developer', $developerId)->where('customer_type', 'SUPPLIER')->first();
        $setting = \DB::table('consolidate_setting')->where('connection_integrate', $connection)->first();
        
        if ($setting && \Carbon\Carbon::parse($setting->next_consolidate)->isFuture()) {
            return;
        }

        $todayInvoices = \DB::table('invoice')
            ->where('id_supplier', $supplier->id_customer)
            ->where('invoice_no', 'LIKE', 'AUTO-' . now()->format('Ymd') . '%')
            ->get();
            
        $count = $todayInvoices->count();
        $amount = $todayInvoices->sum('price');

        if ($setting && $setting->is_send_email == 1 && $count > 0) {
             try {
                $emailData = [
                    'name' => $supplier->registration_name,
                    'count' => $count,
                    'amount' => number_format($amount, 2),
                    'date' => now()->format('d M Y')
                ];

                \Mail::send('emails.auto_consolidate', $emailData, function ($message) use ($supplier) {
                    $cc = 'fjusrin@gmail.com';
                    if (!empty($supplier->email)) {
                        $message->to($supplier->email)->cc($cc);
                    } else {
                        $message->to($cc);
                    }
                    $message->subject('Auto-Consolidation Completed');
                });
            } catch (\Exception $e) {
                \Log::error("Email failed: " . $e->getMessage());
            }
        }

        $nextDate = null;
        $now = now();

        if ($setting->is_daily) $nextDate = $now->copy()->addDay()->startOfDay();
        elseif ($setting->is_weekly) $nextDate = $now->copy()->next('Sunday')->startOfDay();
        elseif ($setting->is_monthly) $nextDate = $now->copy()->addMonth()->endOfMonth()->startOfDay();
        elseif ($setting->is_spesific_date) {
            $candidate = $now->copy()->day($setting->is_spesific_date)->startOfDay();
            $nextDate = ($candidate->lte($now)) ? $now->copy()->addMonth()->day($setting->is_spesific_date)->startOfDay() : $candidate;
        }

        if ($nextDate) {
            \DB::table('consolidate_setting')
                ->where('connection_integrate', $connection)
                ->update(['last_consolidate' => now(), 'next_consolidate' => $nextDate]);
        }
    }
}
