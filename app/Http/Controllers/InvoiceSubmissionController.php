<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Log;
use App\Jobs\SubmitInvoicesBatch;

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
        ->where('i.is_deleted', 0) // <======== 1. ADD THIS LINE HERE
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

    /*
    |--------------------------------------------------------------------------
    | Status Counts (for dashboard cards) - SIMPLIFIED APPROACH
    |--------------------------------------------------------------------------
    */
    $statusCounts = [
        'Submitted' => 0,
        'Pending' => 0,
        'Failed' => 0,
    ];

    // Only calculate counts if a connection is selected
    if ($request->filled('connection_integrate') && $request->connection_integrate !== 'ALL') {
        
        // Get all invoices for this connection with filters applied
        $countQuery = DB::table('invoice AS i')
            ->where('i.id_developer', $developerId)
            ->where('i.connection_integrate', $request->connection_integrate);

        // Apply date filters
        if ($request->start_date) {
            $countQuery->whereDate('i.issue_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $countQuery->whereDate('i.issue_date', '<=', $request->end_date);
        }
        if ($request->invoice_type && $request->invoice_type !== 'ALL') {
            $countQuery->where('i.invoice_type_code', $request->invoice_type);
        }

        // Get raw counts grouped by status
        $rawCounts = $countQuery
            ->select(
                DB::raw('TRIM(UPPER(submission_status)) as status'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('TRIM(UPPER(submission_status))'))
            ->pluck('total', 'status')
            ->toArray();

        // Map to our status keys
        $statusCounts['Submitted'] = $rawCounts['SUBMITTED'] ?? 0;
        $statusCounts['Pending'] = $rawCounts['PENDING'] ?? 0;
        $statusCounts['Failed'] = $rawCounts['FAILED'] ?? 0;

        // Add any invoices marked as failed via is_failed flag
        $additionalFailed = DB::table('invoice')
            ->where('id_developer', $developerId)
            ->where('connection_integrate', $request->connection_integrate)
            ->where('is_failed', 1)
            ->whereRaw('TRIM(UPPER(submission_status)) != ?', ['FAILED']);

        if ($request->start_date) {
            $additionalFailed->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $additionalFailed->whereDate('issue_date', '<=', $request->end_date);
        }
        if ($request->invoice_type && $request->invoice_type !== 'ALL') {
            $additionalFailed->where('invoice_type_code', $request->invoice_type);
        }

        $statusCounts['Failed'] += $additionalFailed->count();

        // Debug log
        \Log::info('Status Counts Debug', [
            'connection' => $request->connection_integrate,
            'raw_counts' => $rawCounts,
            'final_counts' => $statusCounts
        ]);
    }

    return view('developer.invoice_submissions', compact(
        'customers',
        'invoiceTypes',
        'invoices',
        'statusCounts'
    ));
}

public function consolidate(Request $request)
{
    $developerId = auth()->user()->id;

    $start = $request->input('start_date', now()->startOfMonth()->toDateString());
    $end = $request->input('end_date', now()->endOfMonth()->toDateString());

    session(['consolidate_start' => $start]);
    session(['consolidate_end' => $end]);

    // ✅ FIX: Convert standard dates to include full time ranges (00:00:00 to 23:59:59)
    $queryStart = \Carbon\Carbon::parse($start)->startOfDay();
    $queryEnd = \Carbon\Carbon::parse($end)->endOfDay();

    $selectedConnection = $request->input('connection');

    // ---------------------------------------------------
    // 🚀 CACHE LOGIC: Generate a unique key based on inputs
    // ---------------------------------------------------
    $cacheKey = "consolidate_items_{$developerId}_" . md5($start . $end . $selectedConnection);

    // Cache the query result for 5 minutes (300 seconds)
    $items = \Cache::remember($cacheKey, 300, function () use ($queryStart, $queryEnd, $selectedConnection, $developerId) {
        
        $query = DB::table('consolidate_invoice_item AS cii')
            ->leftJoin('consolidate_invoice AS ci', 'cii.unique_id', '=', 'ci.unique_id')
            ->select(
                'cii.*', 
                'ci.invoice_no' 
            )
            ->whereBetween('cii.issue_date', [$queryStart, $queryEnd]);

        // Only show items that are NOT "set" (New Data Only)
        $query->where(function ($q) {
            $q->where('cii.is_sent_invoice', 0)
              ->orWhereNull('cii.is_sent_invoice');
        });

        // Only show items that are NOT submitted yet
        $query->whereNull('cii.submission_status');

        // Apply Connection Filters
        if ($selectedConnection) {
            $query->where('cii.connection_integrate', $selectedConnection);
        } else {
            $query->where('cii.id_developer', $developerId);
        }

        return $query->orderBy('cii.issue_date')->get();
    });

    // ---------------------------------------------------
    // Available Connections for dropdown (Cached for 1 hour)
    // ---------------------------------------------------
    $connCacheKey = "avail_connections_{$developerId}";
    
    $availableConnections = \Cache::remember($connCacheKey, 3600, function () use ($developerId) {
        return DB::table('customer AS c')
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
    });

    return view('developer.consolidate', compact('items', 'start', 'end', 'availableConnections', 'selectedConnection'));
}
public function ConsolidateSelected(Request $request)
    {
        $developerId = auth()->user()->id;
        
        // FIX 1: Bypass PHP max_input_vars limit by decoding JSON string from frontend
        $rawItems = $request->input('selected_items');
        $selectedIds = is_string($rawItems) ? json_decode($rawItems, true) : (array) $rawItems;

        $selected_connection = $request->input('connection');

        if (empty($selectedIds)) {
            return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
        }

        // Fetch items
        $items = DB::table('consolidate_invoice_item')->whereIn('id_invoice_item', $selectedIds)->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items found.'], 400);
        }

        // FIX 3: Catch connection_integrate if the frontend dropdown is somehow empty
        if (empty($selected_connection)) {
            $selected_connection = $items->first()->connection_integrate;
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

        // Chunking (with fallback if env is missing)
        $chunks = collect($items)->chunk(30);
        $invoiceBaseNo = 'CONSO-' . now()->format('Ymd-His');
        $version = 1;
        
        $totalProcessedAmount = 0; 
        
        // NEW: Track counts for the response message
        $totalBatches = $chunks->count();
        $totalItemsSubmitted = $items->count();
        
        // FIX 4a: Array to collect exceptions during loop
        $submissionErrors = [];

        foreach ($chunks as $chunk) {
            $uniqueId = (string) Str::uuid();
            $invoiceNo = $invoiceBaseNo . '-V' . $version;

            $taxTotal = $chunk->sum('tax');
            
            $subtotal = $chunk->sum(function($item) {
                $calc = ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;
                return $calc > 0 ? $calc : (float) $item->line_extension_amount;
            });
            
            $grandTotal = $subtotal + $taxTotal;
            $totalProcessedAmount += $grandTotal;

            // 1. Create Invoice Header
            $invoiceId = DB::table('invoice')->insertGetId([
                'unique_id' => $uniqueId,
                'connection_integrate' => $selected_connection,
                'invoice_status' => 'manual',
                'submission_status' => 'Pending', 
                'is_failed' => 0,
                'id_developer' => $developerId,
                'id_customer' => 6, 
                'id_supplier' => $customer->id_customer,
                'invoice_no' => $invoiceNo,
                'invoice_type_code' => '01',
                'issue_date' => now(),
                'tax_scheme_id' => 'OTH',
                'tax_category_id'=> '01',
                'price' => number_format((float)$grandTotal, 2, '.', ''), 
                'taxable_amount' => number_format((float)$subtotal, 2, '.', ''),
                'tax_amount' => number_format((float)$taxTotal, 2, '.', ''),
                'payment_note_term' => 'ELECTRONIC TRANSFER',
                
                // FIX 2: Create sale_id_integrate mapping in the parent invoice table
                'sale_id_integrate' => $chunk->first()->sale_id_integrate,
                
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Create Invoice Items
            foreach ($chunk as $index => $item) {
                $calcNet = ($item->price_amount * $item->invoiced_quantity) - $item->price_discount;
                $lineNetAmount = $calcNet > 0 ? $calcNet : (float) $item->line_extension_amount;
                
                $qty = $item->invoiced_quantity > 0 ? $item->invoiced_quantity : 1;
                $priceAmt = $item->price_amount > 0 ? $item->price_amount : $lineNetAmount;

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
                    'invoiced_quantity' => $qty,
                    'line_extension_amount' => number_format((float)$lineNetAmount, 2, '.', ''), 
                    'item_description' => $item->item_description ?: 'Consolidated Item',
                    'price_amount' => number_format((float)$priceAmt, 2, '.', ''),
                    'price_discount' => number_format((float)$item->price_discount, 2, '.', ''),
                    'price_extension_amount' => number_format((float)$lineNetAmount, 2, '.', ''),
                    'tax' => number_format((float)$item->tax, 2, '.', ''), 
                    'item_clasification_value' => '004',
                    'created_at' => now(),
                ]);
            }

            // 3. Update Status (Mark as SET)
            DB::table('consolidate_invoice_item')
                ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
                ->update([
                    'submission_status' => 'submitted', 
                    'is_sent_invoice' => 1, 
                    'updated_at' => now()
                ]);

            // 4. LHDN SUBMISSION LOGIC
            try {
                //session()->forget(['invoice_unique_id', 'selfbill_unique_id', 'invoice_type_code', 'is_selfbilled']);
                //session(['consolidate_status' => '1']); 
                //session(['invoice_unique_id' => $uniqueId, 'invoice_type_code' => '01']);
                
                //$model = new \App\Models\eInvoisModel($selected_connection);
                //$model->submit($invoiceId);

                /*DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                    'submission_status' => 'Submitted',
                    'updated_at' => now()
                ]);*/
            } catch (\Exception $e) {
                \Log::error("Failed to submit Manual-Consolidate Inv #{$invoiceNo}: " . $e->getMessage());
                DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                    'submission_status' => 'Failed',
                    'is_failed' => 1,
                    'updated_at' => now()
                ]);
                
                // FIX 4b: Catch the actual LHDN exception message for the popup
                $submissionErrors[] = "Invoice {$invoiceNo}: " . $e->getMessage();
            }

            $version++;
        }

        $formattedTotal = number_format($totalProcessedAmount, 2);

        // FIX 4c: If any items failed, return a 500 error to trigger the SweetAlert popup
        if (count($submissionErrors) > 0) {
            // Limit to showing the first 3 errors so the popup doesn't overflow the screen
            $errorSummary = implode("<br>", array_slice($submissionErrors, 0, 3));
            if (count($submissionErrors) > 3) $errorSummary .= "<br>...(and more)";
            
            return response()->json([
                'success' => false, 
                'message' => "Items saved, but LHDN rejected some:<br><br>" . $errorSummary
            ], 500); 
        }

        // NEW: Updated Success Response
        return response()->json([
            'success' => true, 
            'message' => "Consolidation successful! \n\nCreated {$totalBatches} batches containing {$totalItemsSubmitted} items.\nTotal Amount: RM {$formattedTotal}"
        ]);
    }    public function view($id_invoice)
    {
        return "Invoice detailed page coming soon. ID: " . $id_invoice;
    }

    public function showInvoice($unique_id)
    {
        // 1. Fetch invoice
        $invoice = DB::table('invoice')
            ->where('unique_id', $unique_id)
            ->first();
    
        if (!$invoice) {
            abort(404, 'Invoice not found.');
        }
    
        // 2. Fetch Supplier
        $supplier = DB::table('customer')
            ->where('id_customer', $invoice->id_supplier)
            ->first();
    
        // 3. Fetch Customer
        $customer = DB::table('customer')
            ->where('id_customer', $invoice->id_customer)
            ->first();
    
        // 4. Fetch Items (Priority: unique_id)
        $items = DB::table('invoice_item')
            ->where('unique_id', $unique_id)
            ->get();
    
        // Fallback old data
        if ($items->isEmpty()) {
            $items = DB::table('invoice_item')
                ->where('id_invoice', $invoice->id_invoice)
                ->get();
        }
    
        // 5. Get State Names (Safe null check)
        $eInvoisModel = new \App\Models\eInvoisModel;
    
        $supplierState = $supplier
            ? $eInvoisModel->getStateNames($supplier->country_subentity_code)
            : null;
    
        $customerState = $customer
            ? $eInvoisModel->getStateNames($customer->country_subentity_code)
            : null;
    
        return view('developer.show_invoice', compact(
            'invoice',
            'customer',
            'supplier',
            'items',
            'supplierState',
            'customerState'
        ));
    }
    
/**
     * ROUTE 1: THE PUSHER (Saves the chunks to the database)
     */
public function submitSelectedInvoices(Request $request)
    {
        if (!$request->ajax()) return response()->json(['error' => 'Invalid request'], 400);

        $selectedIds = $request->input('invoices', []);
        if (empty($selectedIds)) return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);

        // Filter out already submitted invoices to prevent double-billing
        $validIdsToSubmit = DB::table('invoice')
            ->whereIn('id_invoice', $selectedIds)
            ->whereNotIn('submission_status', ['submitted', 'accepted'])
            ->pluck('id_invoice')
            ->toArray();

        if (empty($validIdsToSubmit)) {
            return response()->json(['success' => true, 'message' => 'Selected invoices are already submitted.'], 200);
        }

        $consolidateStatus = session('consolidate_status');
        $connectionIntegrate = $request->connection_integrate;

        // 💡 CHUNK BY 20: This is the safest way to avoid the "Batch size exceeds 5MB" error
        $chunks = array_chunk($validIdsToSubmit, 20); 
        $totalBatches = count($chunks);

        foreach ($chunks as $index => $chunk) {
            // 🚨 CHANGE 1: Added ->onConnection('database') to bypass shared hosting bugs
            // 🚨 CHANGE 2: Removed ->delay(). (Jobs are naturally processed 1-by-1 anyway. 
            // Delaying them hides them from the worker, causing the progress bar to stick).
            SubmitInvoicesBatch::dispatch($chunk, $consolidateStatus, $connectionIntegrate)
                ->onConnection('database');
        }

        return response()->json([
            'success' => true,
            'message' => $totalBatches . ' batches queued successfully.',
            'total_batches' => $totalBatches
        ], 200);
    }

    /**
     * ROUTE 2: THE PINGER
     */
    public function triggerWorker()
    {
        $pendingJobs = DB::table('jobs')->count();
        
        if ($pendingJobs === 0) {
            return response()->json(['status' => 'complete', 'remaining' => 0]);
        }

        try {
            // 🚨 CHANGE 3: Swapped '--max-time' for '--max-jobs => 2'
            // This is the secret to the progress bar! It forces the server to process 
            // exactly 2 batches and immediately send an update to the browser, 
            // making the progress bar move smoothly.
            Artisan::call('queue:work', [
                'database', // Force DB connection
                '--stop-when-empty' => true,
                '--max-jobs' => 2 
            ]);
        } catch (\Exception $e) {
            \Log::error("Worker Crash: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'processing',
            'remaining' => DB::table('jobs')->count()
        ]);
    }

public function stopWorker()
    {
        // Truncate the jobs table to instantly kill any pending/stuck syncs
        \Illuminate\Support\Facades\DB::table('jobs')->truncate();
        
        return response()->json([
            'success' => true, 
            'message' => 'Sync stopped and queue cleared.'
        ]);
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
                'submission_status' => null, // Clear submitted status
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
     * Bulk Soft Delete Invoices
     */
    public function bulkDeleteInvoices(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $selectedIds = $request->input('invoices', []);

        if (empty($selectedIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
        }

        try {
            // Perform the Soft Delete on all selected IDs instantly
            \Illuminate\Support\Facades\DB::table('invoice')
                ->whereIn('id_invoice', $selectedIds)
                ->update([
                    'is_deleted' => 1,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => count($selectedIds) . ' invoice(s) successfully deleted.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System Exception: ' . $e->getMessage()
            ], 500);
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

/**
 * Background batch submission (called by async HTTP requests)
 */
public function submitBatchBackground(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '-1');
    
    $batchData = $request->input('batch');
    $connection = $request->input('connection');
    
    if (empty($batchData) || empty($connection)) {
        return response()->json(['error' => 'Missing batch data'], 400);
    }
    
    try {
        $model = new \App\Models\eInvoisModel($connection);
        $response = $model->submitBatch($batchData);
        
        $responseArray = json_decode(json_encode($response), true);
        $rejectedDocs = $responseArray['original']['rejectedDocuments'] ?? $responseArray['rejectedDocuments'] ?? [];
        
        if (!empty($rejectedDocs)) {
            \Log::error("Batch submission had rejections", ['rejected' => $rejectedDocs]);
        }
        
        return response()->json([
            'success' => true,
            'processed' => count($batchData),
            'rejected' => count($rejectedDocs)
        ]);
        
    } catch (\Exception $e) {
        $errorMsg = $e->getMessage();
        
        // Handle 413 - split and retry
        if ((strpos($errorMsg, '413') !== false || strpos($errorMsg, 'Too Large') !== false) && count($batchData) > 1) {
            $halfSize = (int) ceil(count($batchData) / 2);
            $firstHalf = array_slice($batchData, 0, $halfSize);
            $secondHalf = array_slice($batchData, $halfSize);
            
            \Log::info("413 error - splitting batch", ['original' => count($batchData), 'split_to' => $halfSize]);
            
            // Recursively call with smaller batches
            $this->sendAsyncRequest('/internal/submit-batch-background', [
                'batch' => $firstHalf,
                'connection' => $connection
            ]);
            
            $this->sendAsyncRequest('/internal/submit-batch-background', [
                'batch' => $secondHalf,
                'connection' => $connection
            ]);
            
            return response()->json(['success' => true, 'split' => true]);
        }
        
        // Mark all as failed
        foreach ($batchData as $inv) {
            \DB::table('invoice')->where('id_invoice', $inv['id_invoice'])->update([
                'submission_status' => 'Failed',
                'is_failed' => 1,
                'updated_at' => now()
            ]);
            
            \DB::table('message_header')->updateOrInsert(
                ['id_invoice' => $inv['id_invoice']],
                [
                    'status_submission' => 'ERROR',
                    'error_message' => substr($errorMsg, 0, 500),
                    'response_json' => json_encode(['error' => $errorMsg]),
                    'updated_at' => now()
                ]
            );
        }
        
        \Log::error("Batch submission failed", ['error' => $errorMsg, 'count' => count($batchData)]);
        
        return response()->json(['error' => $errorMsg], 500);
    }
}

/**
 * Send non-blocking async HTTP request
 */
private function sendAsyncRequest($endpoint, $data)
{
    $url = url($endpoint);
    $dataString = json_encode($data);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Don't wait for response
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // 500ms timeout = fire and forget
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataString)
    ]);
    
    // Fire and forget
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Submit multiple batches in true parallel using multi-cURL
 */
private function submitBatchesParallel(array $batches, string $connection)
{
    if (empty($batches)) {
        return [];
    }
    
    $mh = curl_multi_init();
    $handles = [];
    
    // Add all batches to multi-handle
    foreach ($batches as $index => $batch) {
        $ch = curl_init();
        
        $data = json_encode([
            'batch' => $batch,
            'connection' => $connection
        ]);
        
        curl_setopt($ch, CURLOPT_URL, url('/internal/submit-batch-background'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180); // 3 minute timeout per batch
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        
        curl_multi_add_handle($mh, $ch);
        $handles[$index] = $ch;
    }
    
    // Execute all handles simultaneously
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.1); // Wait up to 100ms
    } while ($running > 0);
    
    // Collect results
    $results = [];
    foreach ($handles as $index => $ch) {
        $content = curl_multi_getcontent($ch);
        $results[$index] = $content ? json_decode($content, true) : null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($mh);
    
    return $results;
}
public function consolidateStatus()
{
    $stats = [
        'pending' => \DB::table('consolidate_invoice_item')
            ->where(function ($q) {
                $q->whereNull('submission_status')
                  ->orWhere('submission_status', 'pending');
            })
            ->count(),
        
        'processing_items' => \DB::table('consolidate_invoice_item')
            ->where('submission_status', 'processing')
            ->count(),
        
        'created_items' => \DB::table('consolidate_invoice_item')
            ->where('submission_status', 'created')
            ->count(),
        
        'submitted_items' => \DB::table('consolidate_invoice_item')
            ->where('submission_status', 'submitted')
            ->where('is_sent_invoice', 1)
            ->count(),
        
        'invoices_pending' => \DB::table('invoice')
            ->where('submission_status', 'Pending')
            ->where('is_processing', 0)
            ->count(),
        
        'invoices_processing' => \DB::table('invoice')
            ->where('is_processing', 1)
            ->count(),
        
        'invoices_submitted' => \DB::table('invoice')
            ->where('submission_status', 'Submitted')
            ->count(),
        
        'invoices_failed' => \DB::table('invoice')
            ->where('is_failed', 1)
            ->count(),
        
        'total_invoices' => \DB::table('invoice')->count(),
        
        'invoices_by_status' => \DB::table('invoice')
            ->select('submission_status', \DB::raw('COUNT(*) as count'))
            ->groupBy('submission_status')
            ->get(),
    ];
    
    return response()->json($stats);
}
public function SubmitApi(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '-1');

    $processedBatches = 0;
    $failedResponses = [];
    $successfulInvoices = 0;
    $failedInvoices = 0;
    $batchLimit = 1000;

    \Log::info("=== SUBMIT API STARTED ===");

    // Get and LOCK 1000 pending invoices
    $pendingInvoices = null;
    
    \DB::transaction(function () use (&$pendingInvoices, $batchLimit) {
        $pendingInvoices = \DB::table('invoice')
            ->where('submission_status', 'Pending')
            ->where('is_failed', 0)
            ->where('is_processing', 0)
            ->orderBy('created_at', 'asc')
            ->limit($batchLimit)
            ->lockForUpdate()
            ->get();

        if ($pendingInvoices->isNotEmpty()) {
            $ids = $pendingInvoices->pluck('id_invoice')->toArray();
            
            \DB::table('invoice')
                ->whereIn('id_invoice', $ids)
                ->update([
                    'submission_status' => 'Processing',
                    'is_processing' => 1,
                    'updated_at' => now()
                ]);
        }
    });

    if (!$pendingInvoices || $pendingInvoices->isEmpty()) {
        $pendingCount = \DB::table('invoice')->where('submission_status', 'Pending')->where('is_processing', 0)->count();
        $processingCount = \DB::table('invoice')->where('is_processing', 1)->count();
        
        return response()->json([
            'success' => true,
            'message' => 'No pending invoices to submit',
            'processed' => 0,
            'pending_count' => $pendingCount,
            'processing_count' => $processingCount,
            'can_call_again' => false
        ]);
    }

    \Log::info("Locked {$pendingInvoices->count()} invoices");

    // BULK LOAD ALL CUSTOMERS ONCE
    $uniqueCustomerIds = $pendingInvoices->pluck('id_customer')
        ->merge($pendingInvoices->pluck('id_supplier'))
        ->unique()
        ->filter()
        ->values()
        ->toArray();

    $customersMap = \DB::table('customer')
        ->whereIn('id_customer', $uniqueCustomerIds)
        ->get()
        ->keyBy('id_customer');

    \Log::info("Loaded " . $customersMap->count() . " customers");

    // BULK LOAD ALL INVOICE ITEMS ONCE
    $uniqueIds = $pendingInvoices->pluck('unique_id')->toArray();
    
    $allInvoiceItems = \DB::table('invoice_item')
        ->whereIn('unique_id', $uniqueIds)
        ->get()
        ->groupBy('unique_id');

    \Log::info("Loaded invoice items for " . $allInvoiceItems->count() . " invoices");

    // BULK LOAD CUSTOMER ID 6 (CONSOLIDATE CUSTOMER)
    $consolidateCustomer = \DB::table('customer')->where('id_customer', 6)->first();

    // Group by connection
    $invoicesByConnection = $pendingInvoices->groupBy('connection_integrate');

    foreach ($invoicesByConnection as $connection => $invoices) {
        \Log::info("Processing {$invoices->count()} invoices for connection {$connection}");

        $invoiceBatchData = [];

        foreach ($invoices as $invoice) {
            try {
                $isSelfBill = in_array($invoice->invoice_type_code, ['11', '12', '13', '14']);

                // USE PRE-LOADED CUSTOMERS (NO DB QUERIES)
                if ($isSelfBill) {
                    $supplierRow = $customersMap[$invoice->id_customer] ?? null;
                    $customerRow = $customersMap[$invoice->id_supplier] ?? null;
                } else {
                    $supplierRow = $customersMap[$invoice->id_supplier] ?? null;
                    $customerId = (empty($invoice->id_customer) || $invoice->id_customer == 6) ? 6 : $invoice->id_customer;
                    $customerRow = $customersMap[$customerId] ?? $consolidateCustomer;
                }

                if (!$supplierRow || !$customerRow) {
                    \Log::error("Supplier/Customer not found for invoice {$invoice->invoice_no}");
                    
                    // Save error to message_header
                    \DB::table('message_header')->updateOrInsert(
                        ['id_invoice' => $invoice->id_invoice],
                        [
                            'status_submission' => 'ERROR',
                            'error_message' => 'Supplier or Customer not found in database',
                            'response_json' => json_encode([
                                'error' => 'Supplier/Customer not found',
                                'invoice_no' => $invoice->invoice_no,
                                'id_supplier' => $invoice->id_supplier,
                                'id_customer' => $invoice->id_customer
                            ]),
                            'response_date' => now(),
                            'updated_at' => now()
                        ]
                    );
                    
                    \DB::table('invoice')->where('id_invoice', $invoice->id_invoice)->update([
                        'submission_status' => 'Failed',
                        'is_failed' => 1,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);
                    
                    $failedInvoices++;
                    continue;
                }

                $supplier = [
                    'tin_no' => $supplierRow->tin_no,
                    'registration_name' => $supplierRow->registration_name,
                    'phone' => $supplierRow->phone,
                    'msicCode' => $supplierRow->msicCode ?? null,
                    'email' => $supplierRow->email,
                    'city_name' => $supplierRow->city_name,
                    'postal_zone' => $supplierRow->postal_zone,
                    'country_subentity_code' => $supplierRow->country_subentity_code,
                    'country_code' => $supplierRow->country_code,
                    'address_line_1' => $supplierRow->address_line_1,
                    'address_line_2' => $supplierRow->address_line_2 ?? null,
                    'address_line_3' => $supplierRow->address_line_3 ?? null,
                    'identification_type' => $supplierRow->identification_type ?? null,
                    'identification_no' => $supplierRow->identification_no ?? null
                ];

                $customer = [
                    'tin_no' => $customerRow->tin_no,
                    'sst_registration' => $customerRow->sst_registration ?? null,
                    'registration_name' => $customerRow->registration_name,
                    'phone' => $customerRow->phone,
                    'email' => $customerRow->email,
                    'city_name' => $customerRow->city_name,
                    'postal_zone' => $customerRow->postal_zone,
                    'country_subentity_code' => $customerRow->country_subentity_code,
                    'country_code' => $customerRow->country_code,
                    'address_line_1' => $customerRow->address_line_1,
                    'address_line_2' => $customerRow->address_line_2 ?? null,
                    'address_line_3' => $customerRow->address_line_3 ?? null,
                    'identification_type' => $customerRow->identification_type ?? null,
                    'identification_no' => $customerRow->identification_no ?? null
                ];

                $consolidate_status = (empty($invoice->id_customer) || $invoice->id_customer == 6) ? 1 : 0;
                $delivery = ($consolidate_status == 1 || $invoice->invoice_status == 'manual' || $isSelfBill || $customerRow->tin_no == 'EI00000000010') ? '' : $customer;

                // USE PRE-LOADED INVOICE ITEMS (NO DB QUERIES)
                $invoiceItems = $allInvoiceItems[$invoice->unique_id] ?? collect();
                $items = [];
                foreach ($invoiceItems as $row) {
                    $items[] = [
                        'id_customer' => $row->id_customer,
                        'id_invoice' => $row->id_invoice,
                        'price_discount' => $row->price_discount,
                        'line_id' => $row->line_id,
                        'invoiced_quantity' => $row->invoiced_quantity,
                        'line_extension_amount' => $row->line_extension_amount,
                        'item_description' => $row->item_description,
                        'price_amount' => $row->price_amount,
                        'tax' => $row->tax,
                        'price_extension_amount' => $row->price_extension_amount,
                        'item_clasification_value' => $row->item_clasification_value
                    ];
                }

                $invoiceBatchData[] = [
                    'id_invoice' => $invoice->id_invoice,
                    'unique_id' => $invoice->unique_id,
                    'invoice_status' => $invoice->invoice_status,
                    'invoice_no' => $invoice->invoice_no,
                    'invoice_type_code' => $invoice->invoice_type_code,
                    'issue_date' => $invoice->issue_date,
                    'price' => $invoice->price,
                    'total_price_discount' => $invoice->total_price_discount ?? 0,
                    'taxable_amount' => $invoice->taxable_amount,
                    'tax_amount' => $invoice->tax_amount,
                    'tax_scheme_id' => $invoice->tax_scheme_id,
                    'tax_percent' => $invoice->tax_percent ?? 0,
                    'tax_exemption_reason' => $invoice->tax_exemption_reason ?? null,
                    'payment_note_term' => $invoice->payment_note_term,
                    'payment_financial_account' => $invoice->payment_financial_account ?? null,
                    'include_signature' => $invoice->include_signature ?? true,
                    'uuid' => $invoice->uuid ?? null,
                    'long_id' => $invoice->long_id ?? null,
                    'payment_method' => $invoice->payment_method ?? null,
                    'supplier' => $supplier,
                    'customer' => $customer,
                    'delivery' => $delivery,
                    'items' => $items
                ];

            } catch (\Exception $e) {
                \Log::error("Failed to prepare invoice {$invoice->invoice_no}: " . $e->getMessage());
                
                // Save preparation error to message_header
                \DB::table('message_header')->updateOrInsert(
                    ['id_invoice' => $invoice->id_invoice],
                    [
                        'status_submission' => 'ERROR',
                        'error_message' => substr($e->getMessage(), 0, 500),
                        'response_json' => json_encode([
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'invoice_no' => $invoice->invoice_no
                        ]),
                        'response_date' => now(),
                        'updated_at' => now()
                    ]
                );
                
                \DB::table('invoice')->where('id_invoice', $invoice->id_invoice)->update([
                    'submission_status' => 'Failed',
                    'is_failed' => 1,
                    'is_processing' => 0,
                    'updated_at' => now()
                ]);
                
                $failedInvoices++;
            }
        }

        // Submit in batches of 50 to LHDN
        $batchSize = 200;
        
        foreach (array_chunk($invoiceBatchData, $batchSize) as $batchIndex => $batchChunk) {
            \Log::info("Submitting batch " . ($batchIndex + 1) . "/" . ceil(count($invoiceBatchData) / $batchSize));

            $retryAttempt = 0;
            $maxRetries = 3;
            $batchSuccess = false;

            while ($retryAttempt < $maxRetries && !$batchSuccess) {
                try {
                    $model = new \App\Models\eInvoisModel($connection);
                    $response = $model->submitBatch($batchChunk);

                    $responseArray = json_decode(json_encode($response), true);
                    $rejectedDocs = $responseArray['original']['rejectedDocuments'] ?? $responseArray['rejectedDocuments'] ?? [];

                    if (!empty($rejectedDocs)) {
                        \Log::error("Batch had rejections", ['rejected' => $rejectedDocs]);
                        
                        // Save rejection details to message_header
                        foreach ($rejectedDocs as $rejectedDoc) {
                            $rejectedInvoiceNo = $rejectedDoc['invoiceCodeNumber'] ?? null;
                            if ($rejectedInvoiceNo) {
                                // Find invoice ID from batch
                                $rejectedInvoice = collect($batchChunk)->firstWhere('invoice_no', $rejectedInvoiceNo);
                                
                                if ($rejectedInvoice) {
                                    \DB::table('message_header')->updateOrInsert(
                                        ['id_invoice' => $rejectedInvoice['id_invoice']],
                                        [
                                            'status_submission' => 'REJECTED',
                                            'error_message' => $rejectedDoc['error']['message'] ?? 'Invoice rejected by LHDN',
                                            'response_json' => json_encode($rejectedDoc),
                                            'response_date' => now(),
                                            'updated_at' => now()
                                        ]
                                    );
                                    
                                    \DB::table('invoice')
                                        ->where('invoice_no', $rejectedInvoiceNo)
                                        ->update([
                                            'submission_status' => 'Failed',
                                            'is_failed' => 1,
                                            'is_processing' => 0,
                                            'updated_at' => now()
                                        ]);
                                    
                                    $failedInvoices++;
                                }
                            }
                        }
                    }

                    // Mark successful invoices and save success to message_header
                    $successCount = count($batchChunk) - count($rejectedDocs);
                    if ($successCount > 0) {
                        $successfulIds = [];
                        foreach ($batchChunk as $inv) {
                            $wasRejected = false;
                            foreach ($rejectedDocs as $rejected) {
                                if (isset($rejected['invoiceCodeNumber']) && $rejected['invoiceCodeNumber'] == $inv['invoice_no']) {
                                    $wasRejected = true;
                                    break;
                                }
                            }
                            
                            if (!$wasRejected) {
                                $successfulIds[] = $inv['id_invoice'];
                                
                                // Save success to message_header
                                \DB::table('message_header')->updateOrInsert(
                                    ['id_invoice' => $inv['id_invoice']],
                                    [
                                        'status_submission' => 'SUBMITTED',
                                        'response_json' => json_encode([
                                            'status' => 'success',
                                            'submitted_at' => now()->toDateTimeString()
                                        ]),
                                        'submission_date' => now(),
                                        'response_date' => now(),
                                        'updated_at' => now()
                                    ]
                                );
                            }
                        }
                        
                        if (!empty($successfulIds)) {
                            \DB::table('invoice')
                                ->whereIn('id_invoice', $successfulIds)
                                ->update([
                                    'submission_status' => 'Submitted',
                                    'is_processing' => 0,
                                    'updated_at' => now()
                                ]);
                        }
                    }

                    $successfulInvoices += $successCount;
                    $batchSuccess = true;
                    $processedBatches++;

                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    \Log::error("Batch submission failed", [
                        'error' => $errorMsg,
                        'attempt' => $retryAttempt + 1,
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Handle 413
                    if ((strpos($errorMsg, '413') !== false || strpos($errorMsg, 'Too Large') !== false) && count($batchChunk) > 1) {
                        $halfSize = (int) ceil(count($batchChunk) / 2);
                        $firstHalf = array_slice($batchChunk, 0, $halfSize);
                        $secondHalf = array_slice($batchChunk, $halfSize);

                        try {
                            $model = new \App\Models\eInvoisModel($connection);
                            $model->submitBatch($firstHalf);
                            $model->submitBatch($secondHalf);

                            $allIds = array_column($batchChunk, 'id_invoice');
                            \DB::table('invoice')
                                ->whereIn('id_invoice', $allIds)
                                ->update([
                                    'submission_status' => 'Submitted',
                                    'is_processing' => 0,
                                    'updated_at' => now()
                                ]);

                            $successfulInvoices += count($batchChunk);
                            $batchSuccess = true;
                            $processedBatches++;
                            break;

                        } catch (\Exception $splitError) {
                            \Log::error("Split batch failed: " . $splitError->getMessage());
                            $retryAttempt++;
                        }
                    } else {
                        $retryAttempt++;
                    }

                    // Final failure - save error to message_header for all invoices in batch
                    if ($retryAttempt >= $maxRetries) {
                        foreach ($batchChunk as $inv) {
                            // Save detailed error to message_header
                            \DB::table('message_header')->updateOrInsert(
                                ['id_invoice' => $inv['id_invoice']],
                                [
                                    'status_submission' => 'ERROR',
                                    'error_message' => substr($errorMsg, 0, 500),
                                    'response_json' => json_encode([
                                        'error' => $errorMsg,
                                        'file' => $e->getFile(),
                                        'line' => $e->getLine(),
                                        'trace' => substr($e->getTraceAsString(), 0, 1000),
                                        'invoice_no' => $inv['invoice_no'],
                                        'batch_index' => $batchIndex + 1,
                                        'retry_attempts' => $maxRetries
                                    ]),
                                    'response_date' => now(),
                                    'updated_at' => now()
                                ]
                            );
                            
                            \DB::table('invoice')->where('id_invoice', $inv['id_invoice'])->update([
                                'submission_status' => 'Failed',
                                'is_failed' => 1,
                                'is_processing' => 0,
                                'updated_at' => now()
                            ]);
                        }

                        $failedInvoices += count($batchChunk);
                        $failedResponses[] = [
                            'batch' => $batchIndex + 1,
                            'error' => $errorMsg,
                            'count' => count($batchChunk)
                        ];
                    }
                }
            }
        }
    }

    $remainingPending = \DB::table('invoice')->where('submission_status', 'Pending')->where('is_processing', 0)->count();
    $currentlyProcessing = \DB::table('invoice')->where('is_processing', 1)->count();
    $totalSubmitted = \DB::table('invoice')->where('submission_status', 'Submitted')->count();
    $totalFailed = \DB::table('invoice')->where('is_failed', 1)->count();

    \Log::info("=== SUBMIT API COMPLETED ===", [
        'successful' => $successfulInvoices,
        'failed' => $failedInvoices,
        'remaining' => $remainingPending
    ]);

    return response()->json([
        'success' => true,
        'message' => "Submitted {$successfulInvoices} invoices. {$failedInvoices} failed.",
        'processed_in_this_call' => $pendingInvoices->count(),
        'successful_invoices' => $successfulInvoices,
        'failed_invoices' => $failedInvoices,
        'remaining_pending' => $remainingPending,
        'currently_processing' => $currentlyProcessing,
        'total_submitted' => $totalSubmitted,
        'total_failed' => $totalFailed,
        'can_call_again' => $remainingPending > 0,
        'estimated_calls_remaining' => ceil($remainingPending / $batchLimit),
        'failed_details' => $failedResponses
    ]);
}

public function autoConsolidate(Request $request)
{
    set_time_limit(0);
    ini_set('memory_limit', '-1');

    $processedRetries = 0;
    $processedBatches = 0;
    $createdInvoices = 0;

    // ====================================================================
    // PHASE 1: RESUBMIT FAILED INVOICES (Background Sweep)
    // ====================================================================
    $failedInvoices = \DB::table('invoice')
        ->where('is_failed', 1)
        ->where('submission_status', 'Failed')
        ->limit(10)
        ->get();

    if ($failedInvoices->isNotEmpty()) {
        $ids = $failedInvoices->pluck('id_invoice')->toArray();
        \DB::table('invoice')
            ->whereIn('id_invoice', $ids)
            ->update(['submission_status' => 'Pending', 'is_failed' => 0, 'updated_at' => now()]);
        
        $processedRetries = $failedInvoices->count();
        \Log::info("Marked {$processedRetries} failed invoices as Pending for retry");
    }

    // ====================================================================
    // PHASE 2: CONSOLIDATE NEW ITEMS (Create Invoices Only)
    // ====================================================================
    $candidates = \DB::table('consolidate_setting')
        ->join('customer', 'consolidate_setting.connection_integrate', '=', 'customer.connection_integrate')
        ->where('consolidate_setting.is_enabled', 1)
        ->where('consolidate_setting.next_consolidate', '<=', now())
        ->select('customer.id_developer', 'customer.connection_integrate')
        ->distinct()
        ->get();

    if ($candidates->isEmpty() && $processedRetries === 0) {
        return response()->json([
            'success' => true,
            'message' => 'No scheduled consolidations or retries due.',
            'created_invoices' => 0,
            'retried_invoices' => 0
        ]);
    }

    $itemsPerInvoice = 30;
    $batchSize = 10000;

    foreach ($candidates as $candidate) {
        $developerId = $candidate->id_developer;
        $connection = $candidate->connection_integrate;

        $hasMoreItems = true;

        while ($hasMoreItems) {
            $itemsToProcess = null;

            \DB::transaction(function () use ($developerId, $batchSize, &$itemsToProcess) {
                $items = \DB::table('consolidate_invoice_item')
                    ->where('id_developer', $developerId)
                    ->where(function ($q) {
                        $q->whereNull('submission_status')->orWhere('submission_status', 'pending');
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
                        ->update(['submission_status' => 'processing', 'updated_at' => now()]);
                    $itemsToProcess = $items;
                }
            });

            if (empty($itemsToProcess)) {
                $hasMoreItems = false;
                $this->checkAndFinalize($developerId, $connection);
                break;
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
                    $isSelfBill = in_array($invoiceTypeCode, ['11', '12', '13', '14']);

                    $customerId = 6;
                    if ($isSelfBill) {
                        $customerRow = \DB::table('customer')->where('id_customer', $supplier->id_customer)->first();
                        $supplierRow = \DB::table('customer')->where('id_customer', $customerId)->first();
                    } else {
                        $supplierRow = $supplier;
                        $customerRow = \DB::table('customer')->where('id_customer', $customerId)->first();
                    }

                    if (!$supplierRow || !$customerRow) {
                        throw new \Exception("Supplier/Customer not found");
                    }

                    foreach ($chunks as $chunk) {
                        $uniqueId = (string) \Str::uuid();
                        $invoiceNo = $invoiceBaseNo . '-' . strtoupper(\Str::random(4));

                        $totalTax = $chunk->sum('tax');
                        $totalNet = $chunk->sum(function ($item) {
                            $gross = $item->price_amount * $item->invoiced_quantity;
                            return $gross - $item->price_discount;
                        });
                        $payableAmount = $totalNet + $totalTax;

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
                            'tax_category_id' => '01',
                            'price' => number_format((float)$payableAmount, 2, '.', ''),
                            'taxable_amount' => number_format((float)$totalNet, 2, '.', ''),
                            'tax_amount' => number_format((float)$totalTax, 2, '.', ''),
                            'payment_note_term' => 'CASH',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($chunk as $index => $item) {
                            $grossAmount = $item->price_amount * $item->invoiced_quantity;
                            $netAmount = $grossAmount - $item->price_discount;

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
                                'line_extension_amount' => number_format((float)$grossAmount, 2, '.', ''),
                                'price_extension_amount' => number_format((float)$netAmount, 2, '.', ''),
                                'item_description' => $item->item_description,
                                'price_amount' => number_format((float)$item->price_amount, 2, '.', ''),
                                'price_discount' => number_format((float)$item->price_discount, 2, '.', ''),
                                'tax' => number_format((float)$item->tax, 2, '.', ''),
                                'item_clasification_value' => '004',
                                'created_at' => now(),
                            ]);

                            $processedItemIds[] = $item->id_invoice_item;
                        }

                        $createdInvoices++;
                    }

                    // Mark items as ready for submission
                    if (!empty($processedItemIds)) {
                        \DB::table('consolidate_invoice_item')
                            ->whereIn('id_invoice_item', $processedItemIds)
                            ->update(['submission_status' => 'created', 'updated_at' => now()]);
                    }
                }

                $processedBatches++;

            } catch (\Exception $e) {
                \Log::error("Consolidate Batch Error ($developerId): " . $e->getMessage());
                if (!empty($itemsToProcess)) {
                    $itemIds = $itemsToProcess->pluck('id_invoice_item')->toArray();
                    \DB::table('consolidate_invoice_item')
                        ->whereIn('id_invoice_item', $itemIds)
                        ->update(['submission_status' => null]);
                }
                $hasMoreItems = false;
            }
        }
    }

    \Log::info("autoConsolidate completed", [
        'created_invoices' => $createdInvoices,
        'retried_invoices' => $processedRetries
    ]);

    return response()->json([
        'success' => true,
        'message' => "Created {$createdInvoices} invoices. Marked {$processedRetries} failed invoices for retry. Call /developer/submit to submit to LHDN.",
        'created_invoices' => $createdInvoices,
        'retried_invoices' => $processedRetries,
        'next_step' => 'Call /developer/submit to submit invoices to LHDN'
    ]);





}    // Helper to Check Remaining Items
    private function checkAndFinalize($developerId, $connection) {
        $remainingCount = \DB::table('consolidate_invoice_item')
            ->where('id_developer', $developerId)
            ->where(function($q) {
                $q->whereNull('submission_status')->orWhere('submission_status', 'pending')->orWhere('submission_status', 'processing');
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
 // Helper: Email and Reschedule
    private function finalizeConsolidation($developerId, $connection) {
        $supplier = \DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('deleted') // Added this to prevent picking up deleted suppliers
            ->first();
            
        $setting = \DB::table('consolidate_setting')
            ->where('connection_integrate', $connection)
            ->first();
        
        if ($setting && \Carbon\Carbon::parse($setting->next_consolidate)->isFuture()) {
            return;
        }

        // 🚩 FIX: Check if $supplier actually exists before accessing $supplier->id_customer
        if ($supplier) {
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
        }

        // We must calculate the next date regardless of whether a supplier was found, 
        // otherwise this connection will block future crons.
        if ($setting) {
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

    /**
     * Export Unified Invoices (Normal & Self Bill)
     */
    public function export(Request $request)
    {
        $developerId = auth()->user()->id;

        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
            ->leftJoin('invoice_type AS it', 'i.invoice_type_code', '=', 'it.code') // 👉 ADDED: Join to get the Type Name
            ->where('c.id_developer', $developerId)
            ->where('c.customer_type', 'SUPPLIER');

        // Filter by Selected IDs
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('i.id_invoice', $ids);
        } else {
            // Apply Search Filters (Date, Status, Connection)
            if ($request->filled('start_date')) $query->whereDate('i.issue_date', '>=', $request->start_date);
            if ($request->filled('end_date')) $query->whereDate('i.issue_date', '<=', $request->end_date);
            if ($request->filled('status') && $request->status !== 'ALL') $query->where('i.submission_status', $request->status);
            if ($request->filled('connection_integrate') && $request->connection_integrate !== 'ALL') $query->where('i.connection_integrate', $request->connection_integrate);
            
            // 👉 ADDED: Handle Specific Export Type (e.g., from an Export Dropdown) or Main Filter
            if ($request->filled('invoice_type_code')) {
                $query->where('i.invoice_type_code', $request->invoice_type_code);
            } elseif ($request->filled('invoice_type') && $request->invoice_type !== 'ALL') {
                $query->where('i.invoice_type_code', $request->invoice_type);
            }
        }

        $results = $query->select(
            'i.invoice_no', 
            'i.invoice_type_code', 
            'it.description as invoice_type_name', // 👉 ADDED: Select Type Description
            'c.registration_name as supplier_name',
            'c.tin_no as supplier_tin',
            'i.submission_status',
            'i.issue_date', 
            'i.price'
        )->orderBy('i.issue_date', 'desc')->get();

        $filename = "Invoices_Export_" . date('Ymd_His') . ".csv";

        return response()->stream(function() use ($results) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for Excel
            
            // 👉 ADDED 'Type Description' column to the CSV headers
            fputcsv($file, ['Invoice No', 'Type Code', 'Type Description', 'Status', 'Supplier Name', 'TIN', 'Date', 'Total Price']);

            foreach ($results as $row) {
                fputcsv($file, [
                    $row->invoice_no, 
                    $row->invoice_type_code, 
                    $row->invoice_type_name, // 👉 ADDED: Output Type Name in CSV
                    $row->submission_status,
                    $row->supplier_name, 
                    $row->supplier_tin, 
                    $row->issue_date, 
                    $row->price
                ]);
            }
            fclose($file);
        }, 200, [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ]);
    }
    /**
     * Export Consolidated Items (Selected & All)
     */
    public function exportConsolidate(Request $request)
    {
        $developerId = auth()->user()->id;

        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $selectedConnection = $request->input('connection');

        $query = DB::table('consolidate_invoice_item AS cii')
            ->leftJoin('consolidate_invoice AS ci', 'cii.unique_id', '=', 'ci.unique_id')
            ->select(
                'ci.invoice_no', // ✅ FIXED: Changed from cii.invoice_no to ci.invoice_no
                'cii.sale_id_integrate',
                'cii.item_description',
                'cii.invoiced_quantity',
                'cii.tax',
                'cii.line_extension_amount',
                'cii.connection_integrate',
                'cii.issue_date'
            );

        // ✅ ADDED: Export Selected Logic
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('cii.id_invoice_item', $ids);
        } else {
            // Export All (Current Search) Logic
            if ($start && $end) {
                $query->whereBetween('cii.issue_date', [$start, $end]);
            }

            $query->where(function ($q) {
                $q->where('cii.is_sent_invoice', 0)
                  ->orWhereNull('cii.is_sent_invoice');
            });
            $query->whereNull('cii.submission_status');

            if ($selectedConnection) {
                $query->where('cii.connection_integrate', $selectedConnection);
            } else {
                $query->where('cii.id_developer', $developerId);
            }
        }

        $results = $query->orderBy('cii.issue_date')->get();
        $filename = "Consolidate_Items_Export_" . date('Ymd_His') . ".csv";

        return response()->stream(function() use ($results) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for Excel

            fputcsv($file, ['Invoice No.', 'Sale ID', 'Item Name', 'Quantity', 'Tax (RM)', 'Total (RM)', 'Connection', 'Date']);

            foreach ($results as $row) {
                fputcsv($file, [
                    $row->invoice_no ?? '-',
                    $row->sale_id_integrate,
                    $row->item_description,
                    $row->invoiced_quantity,
                    number_format((float)$row->tax, 2, '.', ''),
                    number_format((float)$row->line_extension_amount, 2, '.', ''),
                    $row->connection_integrate,
                    \Carbon\Carbon::parse($row->issue_date)->format('d-m-Y H:i:s')
                ]);
            }
            fclose($file);
        }, 200, [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ]);
    }
    /**
     * Fetch Consolidated Data for DataTables AJAX
     */
/**
     * Fetch Consolidated Data for DataTables AJAX
     */
    public function getConsolidateData(Request $request)
    {
        $developerId = auth()->user()->id;

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $selectedConnection = $request->input('connection');

        $query = DB::table('consolidate_invoice_item AS cii')
            ->leftJoin('consolidate_invoice AS ci', 'cii.unique_id', '=', 'ci.unique_id')
            ->select('cii.*', 'ci.invoice_no');

        if ($start_date && $end_date) {
            $queryStart = \Carbon\Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
            $queryEnd = \Carbon\Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
            $query->whereBetween('cii.issue_date', [$queryStart, $queryEnd]);
        }

        $query->where(function ($q) {
            $q->where('cii.is_sent_invoice', 0)
              ->orWhereNull('cii.is_sent_invoice');
        });

        $query->where(function ($q) {
            $q->whereNull('cii.submission_status')
              ->orWhere('cii.submission_status', '');
        });

        if (!empty($selectedConnection)) {
            $query->where('cii.connection_integrate', $selectedConnection);
        } else {
            $query->where('cii.id_developer', $developerId);
        }

        $totalRecords = $query->count();

        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('ci.invoice_no', 'like', "%{$searchValue}%")
                  ->orWhere('cii.sale_id_integrate', 'like', "%{$searchValue}%")
                  ->orWhere('cii.item_description', 'like', "%{$searchValue}%")
                  ->orWhere('cii.connection_integrate', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // 👉 Calculate totals across all filtered pages BEFORE pagination is applied
        $totalsQuery = clone $query;
        $totalTaxSum = (float) $totalsQuery->sum('cii.tax');
        $totalAmountSum = (float) $totalsQuery->sum('cii.line_extension_amount');

        // Handle Sorting
        $orderColumnIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'asc');
        
        $columns = [
            1 => 'ci.invoice_no',
            2 => 'cii.sale_id_integrate',
            3 => 'cii.item_description',
            4 => 'cii.invoiced_quantity',
            5 => 'cii.tax',
            6 => 'cii.line_extension_amount',
            7 => 'cii.connection_integrate',
            8 => 'cii.issue_date',
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('cii.issue_date', 'desc'); 
        }

        // Handle Pagination
        $start_offset = $request->input('start', 0);
        $length = $request->input('length', 30);
        if ($length != -1) { 
            $query->offset($start_offset)->limit($length);
        }

        $items = $query->get();

        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'id_invoice_item' => $item->id_invoice_item, 
                'invoice_no' => $item->invoice_no,
                'sale_id_integrate' => $item->sale_id_integrate,
                'item_description' => $item->item_description,
                'invoiced_quantity' => $item->invoiced_quantity,
                'tax' => $item->tax,
                'line_extension_amount' => $item->line_extension_amount,
                'connection_integrate' => $item->connection_integrate,
                'issue_date' => $item->issue_date ? \Carbon\Carbon::parse($item->issue_date)->format('d-m-Y H:i:s') : '-',
            ];
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $filteredRecords,
            "totalTaxSum" => $totalTaxSum,       // Sent to frontend
            "totalAmountSum" => $totalAmountSum, // Sent to frontend
            "data" => $data
        ]);
    }
}

