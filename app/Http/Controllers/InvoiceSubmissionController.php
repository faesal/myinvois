<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Log;
use App\Jobs\SubmitInvoicesBatch;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon; 

class InvoiceSubmissionController extends Controller
{
    /**
     * 🚀 HELPER: Reusable query builder to ensure exact filter matching
     * Used for both main views and the background "Select All" fetcher
     */
    private function buildInvoiceQuery(Request $request, $developerId)
    {
        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
            ->leftJoin('connection_integrate AS ci', 'i.connection_integrate', '=', 'ci.code')
            ->leftJoin('invoice_type AS itype', 'i.invoice_type_code', '=', 'itype.code')
            ->leftJoin('invoice_item AS it', function ($join) use ($developerId) {
                $join->on('it.id_invoice', '=', 'i.id_invoice')
                     ->where('it.id_developer', '=', $developerId);
            })
            ->where('ci.id_developer', $developerId)
            ->where('c.id_developer', $developerId)
            ->where('c.customer_type', 'SUPPLIER')
            ->where('i.is_deleted', 0) 
            ->groupBy('i.id_invoice');

        if ($request->start_date) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        if ($request->status && $request->status !== 'ALL') {
            if (strtoupper($request->status) === 'FAILED') {
                $query->where(function($q) use ($request) {
                    $q->where('i.submission_status', $request->status)
                      ->orWhere('i.is_failed', 1);
                });
            } else {
                $query->where('i.submission_status', $request->status);
            }
        }

        if ($request->connection_integrate && $request->connection_integrate !== 'ALL') {
            $query->where('i.connection_integrate', $request->connection_integrate);
            Session::put('connection_integrate', $request->connection_integrate);
        }

        if ($request->invoice_type && $request->invoice_type !== 'ALL') {
            $query->where('i.invoice_type_code', $request->invoice_type);
        }

        return $query;
    }

    /**
     * View Invoice Submissions (Alternative View)
     */
    public function index2(Request $request)
    {
        $developerId = auth()->user()->id;
        $perPage = $request->input('per_page', 50);

        $customers = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->orderBy('registration_name')
            ->get();

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

        if ($request->start_date) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        if ($request->status && $request->status !== 'ALL') {
            if (strtoupper($request->status) === 'FAILED') {
                $query->where(function($q) use ($request) {
                    $q->where('i.submission_status', $request->status)
                      ->orWhere('i.is_failed', 1);
                });
            } else {
                $query->where('i.submission_status', $request->status);
            }
        }

        if ($request->connection_integrate && $request->connection_integrate !== 'ALL') {
            $query->where('i.connection_integrate', $request->connection_integrate);
            Session::put('connection_integrate', $request->connection_integrate);
        }

        // 🚀 OPTIMIZED: Pagination applied
        $invoices = $query->orderBy('i.issue_date', 'desc')->paginate($perPage)->withQueryString();

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
        $perPage = $request->input('per_page', 50);

        $customers = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->orderBy('registration_name')
            ->get();

        $invoiceTypes = DB::table('invoice_type')
            ->orderBy('code')
            ->get();

        // 🚀 OPTIMIZED: Call the helper query builder to match exact filters
        $query = $this->buildInvoiceQuery($request, $developerId);
        
        $query->select(
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
        );

        // 🚀 OPTIMIZED: Pagination applied safely
        $invoices = $query
            ->orderBy('i.issue_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = [
            'Submitted' => 0,
            'Pending' => 0,
            'Failed' => 0,
        ];

        if ($request->filled('connection_integrate') && $request->connection_integrate !== 'ALL') {
            $countQuery = DB::table('invoice AS i')
                ->where('i.id_developer', $developerId)
                ->where('i.connection_integrate', $request->connection_integrate)
                ->where('i.is_deleted', 0); 

            if ($request->start_date) {
                $countQuery->whereDate('i.issue_date', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $countQuery->whereDate('i.issue_date', '<=', $request->end_date);
            }
            if ($request->invoice_type && $request->invoice_type !== 'ALL') {
                $countQuery->where('i.invoice_type_code', $request->invoice_type);
            }

            $rawCounts = $countQuery
                ->select(
                    DB::raw('TRIM(UPPER(submission_status)) as status'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy(DB::raw('TRIM(UPPER(submission_status))'))
                ->pluck('total', 'status')
                ->toArray();

            $statusCounts['Submitted'] = $rawCounts['SUBMITTED'] ?? 0;
            $statusCounts['Pending'] = $rawCounts['PENDING'] ?? 0;
            $statusCounts['Failed'] = $rawCounts['FAILED'] ?? 0;

            $additionalFailed = DB::table('invoice')
                ->where('id_developer', $developerId)
                ->where('connection_integrate', $request->connection_integrate)
                ->where('is_failed', 1)
                ->where('is_deleted', 0) 
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

/**
     * 🚀 NEW ENDPOINT: Fetch ALL matching IDs across all pages
     * Optimized for 100k+ records to prevent memory/timeout crashes
     */
    public function fetchAllMatchingIds(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $developerId = auth()->user()->id;

        try {
            $query = DB::table('invoice AS i')
                ->join('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
                ->where('i.id_developer', $developerId) // 🚀 CRITICAL FIX: Filters the massive invoice table instantly using indexes
                ->where('c.id_developer', $developerId)
                ->where('c.customer_type', 'SUPPLIER')
                ->where('i.is_deleted', 0);

            // Apply Filters manually
            if ($request->start_date) {
                $query->whereDate('i.issue_date', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $query->whereDate('i.issue_date', '<=', $request->end_date);
            }
            if ($request->status && $request->status !== 'ALL') {
                if (strtoupper($request->status) === 'FAILED') {
                    $query->where(function($q) use ($request) {
                        $q->where('i.submission_status', $request->status)
                          ->orWhere('i.is_failed', 1);
                    });
                } else {
                    $query->where('i.submission_status', $request->status);
                }
            }
            if ($request->connection_integrate && $request->connection_integrate !== 'ALL') {
                $query->where('i.connection_integrate', $request->connection_integrate);
            }
            if ($request->invoice_type && $request->invoice_type !== 'ALL') {
                $query->where('i.invoice_type_code', $request->invoice_type);
            }

            // 1. Pluck only the IDs
            $ids = $query->pluck('i.id_invoice')->toArray();

            // 2. 🚀 CRITICAL FIX: Sum columns individually to bypass heavy DB::raw calculations
            $taxableSum = (float) $query->sum('i.taxable_amount');
            $taxSum = (float) $query->sum('i.tax_amount');
            $totalAmount = $taxableSum + $taxSum;

            return response()->json([
                'success' => true,
                'count' => count($ids),
                'ids' => $ids,
                'total_rm' => $totalAmount
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Massive Fetch Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage() // This will now send the EXACT error to your JS
            ], 500); 
        }
    }

    public function consolidate(Request $request)
    {
        $developerId = auth()->user()->id;

        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', now()->endOfMonth()->toDateString());

        session(['consolidate_start' => $start]);
        session(['consolidate_end' => $end]);

        // ✅ FIX: Convert standard dates to include full time ranges (00:00:00 to 23:59:59)
        $queryStart = Carbon::parse($start)->startOfDay();
        $queryEnd = Carbon::parse($end)->endOfDay();

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
                Log::error("Failed to submit Manual-Consolidate Inv #{$invoiceNo}: " . $e->getMessage());
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

        return response()->json([
            'success' => true, 
            'message' => "Consolidation successful! \n\nCreated {$totalBatches} batches containing {$totalItemsSubmitted} items.\nTotal Amount: RM {$formattedTotal}"
        ]);
    }

    public function view($id_invoice)
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
     * ROUTE 1: THE PUSHER (Optimized with 5,000 Hard Cap & Auto-Relay)
     */
    public function submitSelectedInvoices(Request $request)
    {
        if (!$request->ajax()) return response()->json(['error' => 'Invalid request'], 400);

        $rawInvoices = $request->input('invoices');
        $selectedIds = is_string($rawInvoices) ? json_decode($rawInvoices, true) : (array) $rawInvoices;
        
        if (empty($selectedIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
        }

        // Filter valid invoices
        $validIdsToSubmit = \Illuminate\Support\Facades\DB::table('invoice')
            ->whereIn('id_invoice', $selectedIds)
            ->whereNotIn('submission_status', ['submitted', 'accepted'])
            ->pluck('id_invoice')
            ->toArray();

        $totalValid = count($validIdsToSubmit);

        if ($totalValid === 0) {
            return response()->json(['success' => true, 'message' => 'Selected invoices are already submitted.'], 200);
        }

        // ====================================================================
        // 🚀 THE 5,000 SPEED LIMIT: Prevent LHDN Stampedes
        // ====================================================================
        $limit = 5000;
        $processingIds = array_slice($validIdsToSubmit, 0, $limit);
        $queuedCount = count($processingIds);
        $leftoverCount = $totalValid - $queuedCount;

        $consolidateStatus = session('consolidate_status');
        $connectionIntegrate = $request->connection_integrate;

        // Lock the invoices immediately so they show as "Processing"
        \Illuminate\Support\Facades\DB::table('invoice')
            ->whereIn('id_invoice', $processingIds)
            ->update([
                'submission_status' => 'Processing',
                'is_processing' => 1
            ]);

        // Micro-chunks of 10 to isolate bad invoices and limit blast radius
        $microChunks = array_chunk($processingIds, 10); 
        $macroChunks = array_chunk($microChunks, 1); 

        $jobs = [];
        foreach ($macroChunks as $chunkSet) {
            $jobs[] = new \App\Jobs\SubmitInvoicesBatch($chunkSet, $consolidateStatus, $connectionIntegrate);
        }

        // Dispatch as a Laravel Batch allowing failures
        $batch = \Illuminate\Support\Facades\Bus::batch($jobs)
            ->name('Sync LHDN Concurrent - ' . now()->format('Y-m-d H:i:s'))
            ->onConnection('database')
            ->allowFailures() 
            ->dispatch();

        $message = "Processing {$queuedCount} invoices... ";
        if ($leftoverCount > 0) {
            $message .= "({$leftoverCount} remaining. Will auto-continue!)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'batch_id' => $batch->id,
            'has_more' => $leftoverCount > 0, 
            'leftover_count' => $leftoverCount
        ], 200);
    }


/**
     * ROUTE 2: THE TRACKER (With Timezone-Proof Kill Switch & Exact Counting)
     */
    public function checkBatchProgress(Request $request)
    {
        $developerId = auth()->id();

        if (session_id()) {
            session_write_close();
        }

        $batchId = $request->input('batch_id');
        $invoiceIds = $request->input('invoice_ids', []); 

        if (!$batchId) {
            return response()->json(['error' => 'No batch ID provided.'], 400);
        }

        $batch = \Illuminate\Support\Facades\Bus::findBatch($batchId);

        if (!$batch) {
            if ($developerId) {
                $query = \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->where('is_processing', 1);
                    
                if (!empty($invoiceIds)) {
                    $query->whereIn('id_invoice', $invoiceIds);
                }

                $query->update([
                    'submission_status' => 'Pending',
                    'is_failed' => 0,
                    'is_processing' => 0,
                    'updated_at' => now()
                ]);
            }
            return response()->json([
                'status' => 'complete', 'progress' => 100, 'is_cancelled' => true, 'has_failures' => false,
                'error_message' => 'Batch completed or cleared from server.', 'success_count' => 0, 'failed_count' => 0
            ]);
        }

        $progress = $batch->progress();
        $isCancelled = $batch->canceled(); 
        $isFinished = $batch->finished() || $progress >= 100 || $isCancelled;
        
        $errorMessages = [];
        $successCount = 0;
        $failedCount = 0;

        // ====================================================================
        // 🚀 THE FIX: TIMEZONE-PROOF 2-MINUTE KILL SWITCH
        // ====================================================================
        if (!$isFinished) {
            // Safely parse the timestamp and get absolute difference to bypass timezone bugs
            $createdAt = \Carbon\Carbon::parse($batch->createdAt);
            $elapsedSeconds = abs(now()->diffInSeconds($createdAt));
            
            $pendingDbJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
            $isPhantom = ($pendingDbJobs === 0 && $batch->pendingJobs > 0);

            if ($elapsedSeconds >= 120 || $isPhantom) {
                $isFinished = true;
                $isCancelled = true;
                $progress = 100;
                $batch->cancel(); 
                $errorMessages[] = "<b>Timeout (120s)</b>: The API took too long to respond. The batch has been safely stopped.";
            }
        }

        if ($isFinished && $developerId) {
            
            // THE SWEEPER: Revert stuck invoices back to Pending
            $revertQuery = \Illuminate\Support\Facades\DB::table('invoice')
                ->where('id_developer', $developerId)
                ->where('is_processing', 1);
                
            if (!empty($invoiceIds)) {
                $revertQuery->whereIn('id_invoice', $invoiceIds);
            }

            $revertQuery->update([
                'submission_status' => 'Pending', 
                'is_failed' => 0,
                'is_processing' => 0,
                'updated_at' => now()
            ]);

            // ====================================================================
            // 🚀 THE FIX: EXACT COUNTING & ERROR FETCHING
            // ====================================================================
            if (!empty($invoiceIds)) {
                // Get exact Success count
                $successCount = \Illuminate\Support\Facades\DB::table('invoice')
                    ->whereIn('id_invoice', $invoiceIds)
                    ->whereIn('submission_status', ['SUBMITTED', 'ACCEPTED', 'VALID', 'Submitted', 'Accepted', 'Valid'])
                    ->count();

                // Get exact Failed count
                $failedCount = \Illuminate\Support\Facades\DB::table('invoice')
                    ->whereIn('id_invoice', $invoiceIds)
                    ->whereIn('submission_status', ['FAILED', 'ERROR', 'REJECTED', 'Failed', 'Error', 'Rejected'])
                    ->count();

                // Fetch specific errors
                $failedDetails = \Illuminate\Support\Facades\DB::table('invoice')
                    ->leftJoin('message_header', 'invoice.id_invoice', '=', 'message_header.id_invoice')
                    ->whereIn('invoice.id_invoice', $invoiceIds)
                    ->whereIn('invoice.submission_status', ['FAILED', 'ERROR', 'REJECTED', 'Failed', 'Error', 'Rejected'])
                    ->select('invoice.invoice_no', 'message_header.error_message')
                    ->get()
                    ->unique('invoice_no'); 

                foreach ($failedDetails as $fail) {
                    $invNo = $fail->invoice_no ?: 'Unknown Invoice';
                    $msg = $fail->error_message ?: "Validation failed. Please check LHDN requirements.";
                    $errorMessages[] = "<b>{$invNo}</b>: {$msg}"; 
                }
            } else {
                // Fallback calculations
                $successCount = \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->whereIn('submission_status', ['SUBMITTED', 'ACCEPTED', 'VALID', 'Submitted', 'Accepted', 'Valid'])
                    ->where('updated_at', '>=', now()->subMinutes(15))->count();
                    
                $failedCount = \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->whereIn('submission_status', ['FAILED', 'ERROR', 'REJECTED', 'Failed', 'Error', 'Rejected'])
                    ->where('updated_at', '>=', now()->subMinutes(15))->count();
            }

            if ($batch->hasFailures()) {
                $cachedError = \Illuminate\Support\Facades\Cache::get('batch_error_' . $batchId);
                if ($cachedError) $errorMessages[] = "<b>System Error</b>: " . $cachedError;
            }
        }

        $finalErrorMessage = null;
        if (!empty($errorMessages)) {
            $finalErrorMessage = implode("<br><hr style='margin: 5px 0;'>", array_unique($errorMessages));
        }

        return response()->json([
            'status' => $isFinished ? 'complete' : 'processing',
            'progress' => $progress, 
            'is_cancelled' => $isCancelled, // Breaks JS loop instantly
            'remaining_batch' => $batch ? $batch->pendingJobs : 0, 
            'total_batch' => $batch ? $batch->totalJobs : 0,
            'has_failures' => ($batch && $batch->hasFailures()) || !empty($errorMessages) || $failedCount > 0,
            'error_message' => $finalErrorMessage,
            'success_count' => $successCount,     
            'failed_count' => $failedCount        
        ]);
    }

    /**
     * ROUTE 3: THE PINGER (Optimized for Web Hosting Limits)
     */
    public function triggerWorker()
    {
        if (session_id()) {
            session_write_close();
        }

        if (\Illuminate\Support\Facades\Cache::has('lhdn_worker_running')) {
            return response()->json(['status' => 'worker_already_active']);
        }

        // Lock for 20 seconds
        \Illuminate\Support\Facades\Cache::put('lhdn_worker_running', true, 20);

        try {
            \Illuminate\Support\Facades\Artisan::call('queue:work', [
                'connection' => 'database', 
                '--stop-when-empty' => true,
                // 🚀 THE FIX: Reduced to survive PHP's hard max_execution_time limits
                '--max-jobs' => 20, 
                '--max-time' => 20  
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Worker Crash: " . $e->getMessage());
        } finally {
            \Illuminate\Support\Facades\Cache::forget('lhdn_worker_running');
        }

        return response()->json(['status' => 'processed_tick']);
    }


    /**
     * ROUTE 4: THE KILL SWITCH
     */
    public function stopWorker(Request $request)
    {
        try {
            if ($request->has('batch_id') && !empty($request->input('batch_id'))) {
                $batch = \Illuminate\Support\Facades\Bus::findBatch($request->input('batch_id'));
                if ($batch) {
                    $batch->cancel();
                }
            }
            
            // Delete all pending jobs
            \Illuminate\Support\Facades\DB::table('jobs')->delete();
            
            // 🚀 THE STOP FIX: ONLY target invoices currently stuck in 'Processing'.
            // Leave 'Submitted', 'Failed', and 'Pending' completely alone!
            if (auth()->id()) {
                \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', auth()->id())
                    ->where('submission_status', 'Processing') // Shield 1: Only touch Processing status
                    ->where('is_processing', 1)                // Shield 2: Only touch processing flags
                    ->update([
                        'submission_status' => 'Pending',      // Send only the stuck ones back to waiting line
                        'is_failed' => 0,
                        'is_processing' => 0,
                    ]);
            }
            
            return response()->json([
                'success' => true, 
                'message' => 'Sync stopped. Unprocessed invoices have been reverted to Pending.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to stop worker: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to stop worker: ' . $e->getMessage()
            ], 500);
        }
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

        // 🚀 THE FIX: Change 'invoices' to 'ids' to match what your JavaScript sends!
        $selectedIds = $request->input('ids', []);

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
                Log::error("Batch submission had rejections", ['rejected' => $rejectedDocs]);
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
                
                Log::info("413 error - splitting batch", ['original' => count($batchData), 'split_to' => $halfSize]);
                
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
                DB::table('invoice')->where('id_invoice', $inv['id_invoice'])->update([
                    'submission_status' => 'Failed',
                    'is_failed' => 1,
                    'updated_at' => now()
                ]);
                
                DB::table('message_header')->updateOrInsert(
                    ['id_invoice' => $inv['id_invoice']],
                    [
                        'status_submission' => 'ERROR',
                        'error_message' => substr($errorMsg, 0, 500),
                        'response_json' => json_encode(['error' => $errorMsg]),
                        'updated_at' => now()
                    ]
                );
            }
            
            Log::error("Batch submission failed", ['error' => $errorMsg, 'count' => count($batchData)]);
            
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
            'pending' => DB::table('consolidate_invoice_item')
                ->where(function ($q) {
                    $q->whereNull('submission_status')
                      ->orWhere('submission_status', 'pending');
                })
                ->count(),
            
            'processing_items' => DB::table('consolidate_invoice_item')
                ->where('submission_status', 'processing')
                ->count(),
            
            'created_items' => DB::table('consolidate_invoice_item')
                ->where('submission_status', 'created')
                ->count(),
            
            'submitted_items' => DB::table('consolidate_invoice_item')
                ->where('submission_status', 'submitted')
                ->where('is_sent_invoice', 1)
                ->count(),
            
            'invoices_pending' => DB::table('invoice')
                ->where('submission_status', 'Pending')
                ->where('is_processing', 0)
                ->count(),
            
            'invoices_processing' => DB::table('invoice')
                ->where('is_processing', 1)
                ->count(),
            
            'invoices_submitted' => DB::table('invoice')
                ->where('submission_status', 'Submitted')
                ->count(),
            
            'invoices_failed' => DB::table('invoice')
                ->where('is_failed', 1)
                ->count(),
            
            'total_invoices' => DB::table('invoice')->count(),
            
            'invoices_by_status' => DB::table('invoice')
                ->select('submission_status', DB::raw('COUNT(*) as count'))
                ->groupBy('submission_status')
                ->get(),
        ];
        
        return response()->json($stats);
    }

    /**
     * ROUTE: THE BATCH CHECKER & SWEEPER
     * Safely checks the batch and finalizes invoice statuses when 100% complete.
     */
public function checkBatchApi(Request $request)
    {
        // 🚀 FIX 1: Grab the User ID BEFORE closing the session!
        // Using auth()->id() is safer because it won't crash if the user is somehow unauthenticated.
        $developerId = auth()->id();

        // Close session to allow parallel AJAX requests from the frontend
        if (session_id()) {
            session_write_close();
        }

        $batchId = $request->input('batch_id');

        if (!$batchId) {
            return response()->json(['error' => 'No batch ID provided.'], 400);
        }

        $batch = \Illuminate\Support\Facades\Bus::findBatch($batchId);

        // ====================================================================
        // 🚀 FIX 2: NULL-SAFETY & SUCCESS SWEEPER
        // If batch is null, it finished perfectly and Laravel pruned it.
        // We still need to clean up the 'is_processing' flags.
        // ====================================================================
        if (!$batch) {
            if ($developerId) {
                \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->where('is_processing', 1)
                    ->update([
                        'submission_status' => 'Submitted',
                        'is_failed' => 0,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);
            }

            return response()->json([
                'status' => 'complete',
                'progress' => 100, 
                'remaining_batch' => 0, 
                'remaining_invoices' => 0, 
                'total_batch' => 0,
                'has_failures' => false,
                'error_message' => null 
            ]);
        }

        // FETCH CACHED ERROR IF EXISTS
        $errorMsg = null;
        if ($batch->hasFailures()) {
            $errorMsg = \Illuminate\Support\Facades\Cache::get('batch_error_' . $batchId);
        }

        $progress = $batch->progress();
        $isFinished = $batch->finished() || $progress >= 100;

        // ====================================================================
        // 🚀 THE FINAL SWEEPER LOGIC
        // ====================================================================
        if ($isFinished && $developerId) {
            if ($batch->hasFailures()) {
                // Batch failed: Force any stuck processing invoices to Failed
                \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->where('is_processing', 1)
                    ->update([
                        'submission_status' => 'Failed',
                        'is_failed' => 1,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);
            } else {
                // Batch succeeded perfectly: Force any remaining processing invoices to Submitted
                \Illuminate\Support\Facades\DB::table('invoice')
                    ->where('id_developer', $developerId)
                    ->where('is_processing', 1)
                    ->update([
                        'submission_status' => 'Submitted',
                        'is_failed' => 0,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);
            }
        }

        return response()->json([
            'status' => $isFinished ? 'complete' : 'processing',
            'progress' => $progress, 
            'remaining_batch' => $batch->pendingJobs, 
            'remaining_invoices' => $batch->pendingJobs, // Added for frontend compatibility
            'total_batch' => $batch->totalJobs,
            'has_failures' => $batch->hasFailures(),
            'error_message' => $errorMsg 
        ]);
    }
public function SubmitApi(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // ====================================================================
        // 🚀 THE 10,000 SPEED LIMIT
        // ====================================================================
        $limit = 10000;

        Log::info("=== SUBMIT API (BUS BATCH MODE) STARTED ===");

        $validIdsToSubmit = [];
        $rawInvoices = $request->input('invoices');

        // ====================================================================
        // 🛑 NEW: EXPLICITLY FAIL OVERSIZED INVOICES (>30 ITEMS)
        // ====================================================================
        $oversizedQuery = DB::table('invoice')
            ->whereNotIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
            ->where('is_deleted', 0)
            ->whereIn('id_invoice', function ($query) {
                $query->select('id_invoice')
                      ->from('invoice_item')
                      ->groupBy('id_invoice')
                      ->havingRaw('COUNT(id_invoice) > 30'); // Catch > 30
            });

        // If user selected specific invoices in UI, only check those
        if (!empty($rawInvoices)) {
            $checkIds = is_string($rawInvoices) ? json_decode($rawInvoices, true) : (array) $rawInvoices;
            if (!empty($checkIds)) {
                $oversizedQuery->whereIn('id_invoice', $checkIds);
            }
        } else {
            // If Cron mode, only target Pending ones so we don't repeatedly update old failures
            $oversizedQuery->where('submission_status', 'Pending');
        }

        $oversizedIds = $oversizedQuery->pluck('id_invoice')->toArray();

        if (!empty($oversizedIds)) {
            // 1. Mark them as Failed
            DB::table('invoice')
                ->whereIn('id_invoice', $oversizedIds)
                ->update([
                    'submission_status' => 'Failed',
                    'is_failed' => 1,
                    'is_processing' => 0,
                    'updated_at' => now()
                ]);

            // 2. Insert the Error Message so it shows on the UI!
            $errorMessage = "This invoice was rejected because it has more than 30 items.";
            foreach ($oversizedIds as $badId) {
                DB::table('message_header')->updateOrInsert(
                    ['id_invoice' => $badId],
                    [
                        'status_submission' => 'ERROR',
                        'error_message' => $errorMessage,
                        'response_json' => json_encode(['error' => 'System Block: Exceeds 30 items limit']),
                        'updated_at' => now()
                    ]
                );
            }
            Log::warning("Explicitly failed " . count($oversizedIds) . " invoices for having > 30 items.");
        }
        // ====================================================================
        // END EXPLICIT FAIL
        // ====================================================================

        if (!empty($rawInvoices)) {
            // UI / API MODE (Handles massive arrays)
            $selectedIds = is_string($rawInvoices) ? json_decode($rawInvoices, true) : (array) $rawInvoices;
            
            if (empty($selectedIds)) {
                return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
            }

            $validIdsToSubmit = DB::table('invoice')
                ->whereIn('id_invoice', $selectedIds)
                ->whereNotIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
                // 🛑 FILTER: MUST HAVE <= 30 ITEMS
                ->whereIn('id_invoice', function ($query) {
                    $query->select('id_invoice')
                          ->from('invoice_item')
                          ->groupBy('id_invoice')
                          ->havingRaw('COUNT(id_invoice) <= 30');
                })
                ->pluck('id_invoice')
                ->toArray();
        } else {
            // CRON / AUTO MODE
            DB::transaction(function () use (&$validIdsToSubmit, $limit, $request) {
                $query = DB::table('invoice')
                    ->where('submission_status', 'Pending')
                    ->where('is_failed', 0)
                    ->where('is_processing', 0)
                    ->where('is_deleted', 0)
                    // 🛑 FILTER: MUST HAVE <= 30 ITEMS
                    ->whereIn('id_invoice', function ($subQuery) {
                        $subQuery->select('id_invoice')
                              ->from('invoice_item')
                              ->groupBy('id_invoice')
                              ->havingRaw('COUNT(id_invoice) <= 30');
                    });

                // 🚀 THE FIX: THE 5000 BUG ISOLATOR
                if ($request->has('test_rejects') || $request->input('test_rejects') == 1) {
                    $query->where('invoice_no', 'like', 'INV-LHDN-REJ-%');
                }

                $validIdsToSubmit = $query->orderBy('created_at', 'asc')
                    ->limit($limit)
                    ->lockForUpdate()
                    ->pluck('id_invoice')
                    ->toArray();
            });
        }

        $totalValid = count($validIdsToSubmit);

        if ($totalValid === 0) {
            $pendingCount = DB::table('invoice')->where('submission_status', 'Pending')->where('is_processing', 0)->count();
            $processingCount = DB::table('invoice')->where('is_processing', 1)->count();

            return response()->json([
                'success' => true,
                'message' => empty($rawInvoices) ? 'No pending invoices to submit (or they have >30 items).' : 'Selected invoices are already submitted or have >30 items.',
                'processed' => 0,
                'pending_count' => $pendingCount,
                'processing_count' => $processingCount,
                'can_call_again' => false
            ], 200);
        }

        $processingIds = array_slice($validIdsToSubmit, 0, $limit);
        $queuedCount = count($processingIds);
        $leftoverCount = $totalValid - $queuedCount;

        DB::table('invoice')
            ->whereIn('id_invoice', $processingIds)
            ->update([
                'submission_status' => 'Processing',
                'is_processing' => 1,
                'updated_at' => now()
            ]);

        // ====================================================================
        // 2. GROUP BY CONNECTION & APPLY CHUNKING
        // ====================================================================
        $invoicesToGroup = DB::table('invoice')
            ->whereIn('id_invoice', $processingIds)
            ->get(['id_invoice', 'connection_integrate']);

        $invoicesByConnection = $invoicesToGroup->groupBy('connection_integrate');
        
        $consolidateStatus = $request->input('consolidate_status') ?? session('consolidate_status') ?? 0;
        
        $jobs = [];
        
        $connectionsProcessed = $invoicesByConnection->keys()->toArray();

        foreach ($invoicesByConnection as $connection => $invoices) {
            $connectionIds = $invoices->pluck('id_invoice')->toArray();
            
            $microChunks = array_chunk($connectionIds, 50);
            $macroChunks = array_chunk($microChunks, 1);

            foreach ($macroChunks as $chunkSet) {
                $jobs[] = new \App\Jobs\SubmitInvoicesBatch($chunkSet, $consolidateStatus, $connection);
            }
        }

        // ====================================================================
        // 4. DISPATCH BATCH WITH CATCH AND FINALLY BLOCK
        // ====================================================================
        $batch = Bus::batch($jobs)
            ->name('Sync LHDN API/Cron - ' . now()->format('Y-m-d H:i:s'))
            ->onConnection('database')
            ->allowFailures()
            ->catch(function (\Illuminate\Bus\Batch $batch, \Throwable $e) use ($processingIds) {
                Log::error("LHDN API BATCH REJECTED/CRASHED: " . $e->getMessage());
                DB::table('invoice')
                    ->whereIn('id_invoice', $processingIds)
                    ->where('is_processing', 1)
                    ->update([
                        'submission_status' => 'Failed',
                        'is_failed' => 1,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);
            })
            ->finally(function (\Illuminate\Bus\Batch $batch) use ($connectionsProcessed, $processingIds) {
                
                DB::table('invoice')
                    ->whereIn('id_invoice', $processingIds)
                    ->where('is_processing', 1)
                    ->update([
                        'submission_status' => 'Failed',
                        'is_failed' => 1,
                        'is_processing' => 0,
                        'updated_at' => now()
                    ]);

                foreach ($connectionsProcessed as $connection) {
                    $setting = DB::table('consolidate_setting')
                        ->where('connection_integrate', $connection)
                        ->first();

                    if ($setting && $setting->is_send_email == 1) {
                        $supplier = DB::table('customer')
                            ->where('connection_integrate', $connection)
                            ->where('customer_type', 'SUPPLIER')
                            ->whereNull('deleted')
                            ->first();

                        if ($supplier) {
                            $successInvoices = DB::table('invoice')
                                ->whereIn('id_invoice', $processingIds)
                                ->where('connection_integrate', $connection)
                                ->whereIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
                                ->get();

                            $failedInvoicesCount = DB::table('invoice')
                                ->whereIn('id_invoice', $processingIds)
                                ->where('connection_integrate', $connection)
                                ->where(function($q) {
                                    $q->where('submission_status', 'Failed')
                                      ->orWhere('is_failed', 1);
                                })
                                ->count();

                            $successCount = $successInvoices->count();

                            if ($successCount > 0 || $failedInvoicesCount > 0) {
                                $amount = $successInvoices->sum('price');

                                try {
                                    $emailData = [
                                        'name' => $supplier->registration_name,
                                        'count' => $successCount,
                                        'failed_count' => $failedInvoicesCount,
                                        'amount' => number_format($amount, 2),
                                        'date' => now()->format('d M Y')
                                    ];

                                    Mail::send('emails.auto_consolidate', $emailData, function ($message) use ($supplier) {
                                        if (!empty($supplier->email)) {
                                            $message->to($supplier->email);
                                        } else {
                                            Log::warning("No email found to notify supplier {$supplier->registration_name}");
                                        }
                                        $message->subject('e-Invoice Submission Batch Completed');
                                    });
                                    
                                    Log::info("Success/Fail Email sent for {$supplier->registration_name}. Success: {$successCount}, Failed: {$failedInvoicesCount}");

                                } catch (\Exception $e) {
                                    Log::error("Submission Email failed for {$connection}: " . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            })
            ->dispatch();

        $remainingPending = DB::table('invoice')->where('submission_status', 'Pending')->where('is_processing', 0)->count();
        $currentlyProcessing = DB::table('invoice')->where('is_processing', 1)->count();

        $message = "Processing {$queuedCount} invoices... ";
        if ($leftoverCount > 0) {
            $message .= "({$leftoverCount} remaining in selection. Will auto-continue!)";
        } elseif (empty($rawInvoices) && $remainingPending > 0) {
            $message .= "({$remainingPending} remaining in database.)";
        } else {
            $message .= "across " . count($jobs) . " concurrent jobs.";
        }

        Log::info("=== SUBMIT API DISPATCH COMPLETED === | " . $message);

        // 🚀 6. FIRE-AND-FORGET HTTP PING
        try {
            $workerUrl = request()->getSchemeAndHttpHost() . '/api/worker/trigger-api';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $workerUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => $message,
            'batch_id' => $batch->id,
            'processed_in_this_call' => $queuedCount,
            'has_more' => ($leftoverCount > 0 || (empty($rawInvoices) && $remainingPending > 0)),
            'leftover_count' => $leftoverCount,
            'remaining_pending' => $remainingPending,
            'currently_processing' => $currentlyProcessing,
            'can_call_again' => ($leftoverCount > 0 || (empty($rawInvoices) && $remainingPending > 0))
        ], 200);
    }

    /**
     * ROUTE: THE PINGER
     */
    public function triggerWorkerAPI()
    {
        if (session_id()) {
            session_write_close();
        }

        $workerError = null;

        try {
            Log::info("Worker successfully hit via ping. Starting queue...");

            // 🚀 THE FIX: Put the time limit back to stop the 504 Gateway Timeout!
            // --stop-when-empty in Laravel must be true/false, not a number.
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--queue' => 'default',
                '--stop-when-empty' => true, 
                '--max-time' => 30, // Automatically stops the PHP script at 30 seconds so Nginx doesn't crash at 60s
            ]);

        } catch (\Exception $e) {
            Log::error("Worker Crash in triggerWorker: " . $e->getMessage() . " on line " . $e->getLine());
            $workerError = $e->getMessage();
        }

        // ====================================================================
        // 🚀 THE FIX: THE UNSTOPPABLE SWEEPER (WITH 15-MIN GRACE PERIOD)
        // ====================================================================
        $stuckCount = DB::table('invoice')
            ->where('is_processing', 1)
            ->whereNotIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
            ->where('updated_at', '<', now()->subMinutes(15)) // <-- THIS PREVENTS ACTIVE INVOICES FROM FAILING
            ->update([
                'submission_status' => 'Failed',
                'is_failed' => 1,
                'is_processing' => 0,
                'updated_at' => now()
            ]);

        if ($workerError) {
            return response()->json([
                'status' => 'error',
                'message' => "Job failed with Exception: " . $workerError,
                'forced_failures_found' => $stuckCount
            ], 500);
        }

        return response()->json([
            'status' => 'processed_tick',
            'message' => 'Worker finished running cleanly (or safely hit the time limit).',
            'forced_failures_found' => $stuckCount
        ]);
    }

    public function retryFailedApi(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $limit = 5000;
        Log::info("=== RETRY FAILED API STARTED ===");

        // ====================================================================
        // 🚀 0. ORPHAN CLEANUP (Safety Net) - REDUCED TO 1 MINUTE FOR TESTING
        // ====================================================================
        DB::table('invoice')
            ->where('is_processing', 1)
            ->whereNotIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
            ->where('updated_at', '<', now()->subMinutes(1)) // <--- CHANGED THIS TO 1 MINUTE
            ->update([
                'submission_status' => 'Failed',
                'is_failed' => 1,
                'is_processing' => 0,
                'updated_at' => now()
            ]);

        // ====================================================================
        // 1. CHECK MAX RETRIES (3) -> CONSOLIDATE & SEND FAILURE EMAIL
        // ====================================================================
        $maxFailedInvoices = DB::table('invoice')
            ->join('customer', 'invoice.id_supplier', '=', 'customer.id_customer')
            ->leftJoin('message_header', 'invoice.id_invoice', '=', 'message_header.id_invoice')
            ->where('invoice.is_failed', 1)
            ->where('invoice.is_deleted', 0)
            ->where('invoice.retry_count', '>=', 3)
            ->where('invoice.is_failed_email_sent', 0)
            ->select(
                'invoice.id_invoice', 
                'invoice.invoice_no', 
                'customer.id_customer',
                'customer.email', 
                'customer.registration_name',
                'message_header.error_message'
            )
            ->get();

        if ($maxFailedInvoices->isNotEmpty()) {
            // Group invoices by customer to send ONE email per supplier
            $groupedInvoices = $maxFailedInvoices->groupBy('id_customer');

            foreach ($groupedInvoices as $customerId => $invoices) {
                try {
                    $supplierInfo = $invoices->first();
                    $adminEmails = ['faesal09@gmail.com', 'fjusrin@gmail.com']; 
                    $invoiceIds = $invoices->pluck('id_invoice')->toArray();
                    
                    // Prepare bundled data for the email template
                    $emailData = [
                        'supplier_name' => $supplierInfo->registration_name,
                        'total_failed' => $invoices->count(),
                        'failed_invoices' => $invoices
                    ];

                    Mail::send('emails.failed_invoice_alert', $emailData, function ($message) use ($supplierInfo, $adminEmails, $emailData) {
                        if (!empty($supplierInfo->email)) {
                            $message->to($supplierInfo->email);
                            $message->cc($adminEmails);
                        } else {
                            $message->to($adminEmails);
                        }
                        $message->subject("URGENT: {$emailData['total_failed']} e-Invoices Failed to Submit");
                    });

                    // Update the flag for ALL invoices in this bundle at once
                    DB::table('invoice')
                        ->whereIn('id_invoice', $invoiceIds)
                        ->update(['is_failed_email_sent' => 1]);
                        
                } catch (\Exception $e) {
                    Log::error("Failed batch email for Customer {$customerId}: " . $e->getMessage());
                }
            }
        }

        // ====================================================================
        // 2. FETCH FAILED INVOICES FOR RETRY
        // ====================================================================
        $validIdsToRetry = [];

        DB::transaction(function () use (&$validIdsToRetry, $limit) {
            $validIdsToRetry = DB::table('invoice')
                ->where(function($q) {
                    $q->where('submission_status', 'Failed')
                      ->orWhere('is_failed', 1);
                })
                ->where('is_deleted', 0)
                ->whereNotIn('submission_status', ['submitted', 'accepted', 'Submitted', 'Accepted', 'SUBMITTED', 'ACCEPTED'])
                ->where('is_processing', 0)
                ->where('retry_count', '<', 3)
                ->whereIn('id_invoice', function ($query) {
                    $query->select('id_invoice')
                          ->from('invoice_item')
                          ->groupBy('id_invoice')
                          ->havingRaw('COUNT(id_invoice) <= 30');
                })
                ->orderBy('updated_at', 'asc')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id_invoice')
                ->toArray();
        });

        if (count($validIdsToRetry) === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No eligible failed invoices to retry.',
            ], 200);
        }

        DB::table('invoice')
            ->whereIn('id_invoice', $validIdsToRetry)
            ->update([
                'submission_status' => 'Processing',
                'is_processing' => 1,
                'retry_count' => DB::raw('retry_count + 1'),
                'updated_at' => now()
            ]);

        $invoicesToGroup = DB::table('invoice')
            ->whereIn('id_invoice', $validIdsToRetry)
            ->get(['id_invoice', 'connection_integrate']);

        $invoicesByConnection = $invoicesToGroup->groupBy('connection_integrate');
        $consolidateStatus = 0;
        $jobs = [];
        $processingIds = $validIdsToRetry;

        foreach ($invoicesByConnection as $connection => $invoices) {
            $connectionIds = $invoices->pluck('id_invoice')->toArray();
            $microChunks = array_chunk($connectionIds, 50);
            foreach (array_chunk($microChunks, 1) as $chunkSet) {
                $jobs[] = new \App\Jobs\SubmitInvoicesBatch($chunkSet, $consolidateStatus, $connection);
            }
        }

        // ====================================================================
        // 4. DISPATCH BATCH
        // ====================================================================
        $batch = Bus::batch($jobs)
            ->name('Retry LHDN API - ' . now()->format('Y-m-d H:i:s'))
            ->onConnection('database')
            ->allowFailures()
            ->catch(function (\Illuminate\Bus\Batch $batch, \Throwable $e) use ($processingIds) {
                DB::table('invoice')
                    ->whereIn('id_invoice', $processingIds)
                    ->where('is_processing', 1)
                    ->update(['submission_status' => 'Failed', 'is_failed' => 1, 'is_processing' => 0, 'updated_at' => now()]);
            })
            ->finally(function (\Illuminate\Bus\Batch $batch) use ($processingIds) {
                DB::table('invoice')
                    ->whereIn('id_invoice', $processingIds)
                    ->where('is_processing', 1)
                    ->update(['submission_status' => 'Failed', 'is_failed' => 1, 'is_processing' => 0, 'updated_at' => now()]);
            })
            ->dispatch();

        // 🚀 UPDATED HTTP PING TO BE MORE FORGIVING ON LOCALHOST
        try {
            $workerUrl = url('/api/worker/trigger-api');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $workerUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // Give it a tiny bit more time to connect
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error("Ping failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Retrying ' . count($validIdsToRetry) . ' failed invoices.',
            'batch_id' => $batch->id,
            'processed' => count($validIdsToRetry),
        ], 200);
    }

 public function autoConsolidate(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $processedRetries = 0;
        $processedBatches = 0;
        $createdInvoices = 0;

        $failedInvoices = DB::table('invoice')
            ->where('is_failed', 1)
            ->where('submission_status', 'Failed')
            ->limit(10)
            ->get();

        if ($failedInvoices->isNotEmpty()) {
            $ids = $failedInvoices->pluck('id_invoice')->toArray();
            DB::table('invoice')
                ->whereIn('id_invoice', $ids)
                ->update(['submission_status' => 'Pending', 'is_failed' => 0, 'updated_at' => now()]);
            
            $processedRetries = $failedInvoices->count();
            Log::info("Marked {$processedRetries} failed invoices as Pending for retry");
        }

        $candidates = DB::table('consolidate_setting')
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
            $createdInvoicesForCandidate = 0; // Track invoices created in this specific run

            while ($hasMoreItems) {
                $itemsToProcess = null;

                DB::transaction(function () use ($developerId, $batchSize, &$itemsToProcess) {
                    $items = DB::table('consolidate_invoice_item')
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
                        DB::table('consolidate_invoice_item')
                            ->whereIn('id_invoice_item', $itemIds)
                            ->update(['submission_status' => 'processing', 'updated_at' => now()]);
                        $itemsToProcess = $items;
                    }
                });

                if (empty($itemsToProcess)) {
                    $hasMoreItems = false;
                    // Pass the local counter to checkAndFinalize
                    $this->checkAndFinalize($developerId, $connection, $createdInvoicesForCandidate);
                    break;
                }

                try {
                    $supplier = DB::table('customer')
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
                            $customerRow = DB::table('customer')->where('id_customer', $supplier->id_customer)->first();
                            $supplierRow = DB::table('customer')->where('id_customer', $customerId)->first();
                        } else {
                            $supplierRow = $supplier;
                            $customerRow = DB::table('customer')->where('id_customer', $customerId)->first();
                        }

                        if (!$supplierRow || !$customerRow) {
                            throw new \Exception("Supplier/Customer not found");
                        }

                        foreach ($chunks as $chunk) {
                            $uniqueId = (string) Str::uuid();
                            $invoiceNo = $invoiceBaseNo . '-' . strtoupper(Str::random(4));

                            $totalTax = $chunk->sum('tax');
                            $totalNet = $chunk->sum(function ($item) {
                                $gross = $item->price_amount * $item->invoiced_quantity;
                                return $gross - $item->price_discount;
                            });
                            $payableAmount = $totalNet + $totalTax;

                            $invoiceId = DB::table('invoice')->insertGetId([
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

                                DB::table('invoice_item')->insert([
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
                            $createdInvoicesForCandidate++; // Increment local counter
                        }

                        if (!empty($processedItemIds)) {
                            DB::table('consolidate_invoice_item')
                                ->whereIn('id_invoice_item', $processedItemIds)
                                ->update(['submission_status' => 'created', 'updated_at' => now()]);
                        }
                    }

                    $processedBatches++;

                } catch (\Exception $e) {
                    Log::error("Consolidate Batch Error ($developerId): " . $e->getMessage());
                    if (!empty($itemsToProcess)) {
                        $itemIds = $itemsToProcess->pluck('id_invoice_item')->toArray();
                        DB::table('consolidate_invoice_item')
                            ->whereIn('id_invoice_item', $itemIds)
                            ->update(['submission_status' => null]);
                    }
                    $hasMoreItems = false;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Created {$createdInvoices} invoices. Marked {$processedRetries} failed invoices for retry. Call /developer/submit to submit to LHDN.",
            'created_invoices' => $createdInvoices,
            'retried_invoices' => $processedRetries,
        ]);
    }

    private function checkAndFinalize($developerId, $connection, $createdInvoicesForCandidate = 0) {
        $remainingCount = DB::table('consolidate_invoice_item')
            ->where('id_developer', $developerId)
            ->where(function($q) {
                $q->whereNull('submission_status')->orWhere('submission_status', 'pending')->orWhere('submission_status', 'processing');
            })
            ->where(function ($q) {
                $q->where('is_sent_invoice', 0)->orWhereNull('is_sent_invoice');
            })
            ->count();

        if ($remainingCount === 0) {
            $this->finalizeConsolidation($developerId, $connection, $createdInvoicesForCandidate);
        }
    }

    private function finalizeConsolidation($developerId, $connection, $createdInvoicesForCandidate = 0) {
        $supplier = DB::table('customer')
            ->where('id_developer', $developerId)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('deleted')
            ->first();
            
        $setting = DB::table('consolidate_setting')
            ->where('connection_integrate', $connection)
            ->first();
        
        if ($setting && Carbon::parse($setting->next_consolidate)->isFuture()) {
            return;
        }

        if ($supplier) {
            $todayInvoices = DB::table('invoice')
                ->where('id_supplier', $supplier->id_customer)
                ->where('invoice_no', 'LIKE', 'AUTO-' . now()->format('Ymd') . '%')
                ->get();
                
            $count = $todayInvoices->count();
            $amount = $todayInvoices->sum('price');

            // Ensure we ONLY send an email if new invoices were actually created during THIS specific cron run
            if ($setting && $setting->is_send_email == 1 && $createdInvoicesForCandidate > 0) {
                 try {
                    $emailData = [
                        'name' => $supplier->registration_name,
                        'count' => $count, // We still pass the total daily count to the email body
                        'amount' => number_format($amount, 2),
                        'date' => now()->format('d M Y')
                    ];

                    Mail::send('emails.auto_consolidate', $emailData, function ($message) use ($supplier) {
                        if (!empty($supplier->email)) {
                            $message->to($supplier->email);
                        } else {
                            Log::warning("No email found to notify supplier {$supplier->registration_name}");
                        }
                        // --> ADD THIS LINE for your admin email
                          $message->bcc('fjusrin@gmail.com');
                        $message->subject('Auto-Consolidation Completed');
                    });
                } catch (\Exception $e) {
                    Log::error("Email failed: " . $e->getMessage());
                }
            }
        }

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
                DB::table('consolidate_setting')
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
                    Carbon::parse($row->issue_date)->format('d-m-Y H:i:s')
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
            $queryStart = Carbon::parse($start_date)->startOfDay()->format('Y-m-d H:i:s');
            $queryEnd = Carbon::parse($end_date)->endOfDay()->format('Y-m-d H:i:s');
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
                'issue_date' => $item->issue_date ? Carbon::parse($item->issue_date)->format('d-m-Y H:i:s') : '-',
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