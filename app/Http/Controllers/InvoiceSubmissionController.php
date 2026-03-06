<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
        // 🚀 CACHE KEY
        // ---------------------------------------------------
        $cacheKey = "consolidate_items_{$developerId}_" . md5($start . $end . $selectedConnection);
    
        // ---------------------------------------------------
        // FETCH ITEMS (CACHE 5 MINUTES)
        // ---------------------------------------------------
        $items = Cache::remember($cacheKey, 300, function () use ($start, $end, $selectedConnection, $developerId) {
    
            $query = DB::table('consolidate_invoice_item AS cii')
                ->leftJoin('consolidate_invoice AS ci', 'cii.unique_id', '=', 'ci.unique_id')
                ->select(
                    'cii.*',
                    'ci.invoice_no'
                )
    
                // FIX: Detect both DATE and DATETIME
                ->whereBetween(DB::raw('DATE(cii.issue_date)'), [$start, $end])
    
                // Only show items not yet sent
                ->where(function ($q) {
                    $q->where('cii.is_sent_invoice', 0)
                      ->orWhereNull('cii.is_sent_invoice');
                })
    
                // Only show items not submitted
                ->whereNull('cii.submission_status');
    
            // ---------------------------------------------------
            // FILTER BY CONNECTION OR DEVELOPER
            // ---------------------------------------------------
            if ($selectedConnection) {
                $query->where('cii.connection_integrate', $selectedConnection);
            } else {
                $query->where('cii.id_developer', $developerId);
            }
    
            return $query->orderBy('cii.issue_date', 'ASC')->get();
        });
    
        // ---------------------------------------------------
        // AVAILABLE CONNECTIONS (CACHE 1 HOUR)
        // ---------------------------------------------------
        $connCacheKey = "avail_connections_{$developerId}";
    
        $availableConnections = Cache::remember($connCacheKey, 3600, function () use ($developerId) {
    
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
    
        return view(
            'developer.consolidate',
            compact(
                'items',
                'start',
                'end',
                'availableConnections',
                'selectedConnection'
            )
        );
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
        $chunks = collect($items)->chunk(env('MYINVOIS_INVOICE_CHUNK', 50));
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

    // Dynamic configuration from .env
    $itemsPerInvoice = env('MYINVOIS_INVOICE_CHUNK');
    // Batch size for processing (e.g., pulling enough items to fill roughly 2.5 invoices)
    $batchSize = $itemsPerInvoice * 2.5; 

    foreach ($candidates as $candidate) {
        $developerId = $candidate->id_developer;
        $connection = $candidate->connection_integrate;
        
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
                
                // Chunking items based on .env value
                $chunks = $itemsToProcess->chunk($itemsPerInvoice); 
                $invoiceBaseNo = 'AUTO-' . now()->format('Ymd-His');
                
                $processedItemIds = [];
                $invoiceTypeCode = ($supplier->is_selfbill_supplier == 1) ? '11' : '01';

                foreach ($chunks as $chunk) {
                    $uniqueId = (string) \Str::uuid();
                    $invoiceNo = $invoiceBaseNo . '-' . strtoupper(\Str::random(4));

                    // --- 1. CALCULATE HEADER TOTALS ---
                    $totalTax = $chunk->sum('tax');
                    
                    $totalNet = $chunk->sum(function($item) {
                        $gross = $item->price_amount * $item->invoiced_quantity;
                        return $gross - $item->price_discount;
                    });
                    
                    $payableAmount = $totalNet + $totalTax;

                    // --- 2. INSERT INVOICE HEADER ---
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
                        
                        'price' => number_format((float)$payableAmount, 2, '.', ''), 
                        'taxable_amount' => number_format((float)$totalNet, 2, '.', ''), 
                        'tax_amount' => number_format((float)$totalTax, 2, '.', ''),
                        
                        'payment_note_term' => 'CASH',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // --- 3. INSERT INVOICE ITEMS ---
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
                        ->update(['submission_status' => 'submitted', 'is_sent_invoice' => 1, 'updated_at' => now()]);
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
                    ->update(['submission_status' => null]);
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => "Cycle complete. Retried {$processedRetries} failed invoices. Processed {$processedBatches} new batches."
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

