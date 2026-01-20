<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

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
    // We use a Left Join to get the invoice_no from the invoice table 
    // based on the shared sale_id_integrate.
    $query = DB::table('consolidate_invoice_item AS cii')
        ->leftJoin('invoice AS i', 'cii.sale_id_integrate', '=', 'i.sale_id_integrate')
        ->select(
            'cii.*', 
            'i.invoice_no' // This allows {{ $item->invoice_no }} to work in Blade
        )
        ->whereBetween('cii.issue_date', [$start, $end]);

    // Apply Filter Logic
    if ($selectedConnection) {
        $query->where('cii.connection_integrate', $selectedConnection)
            ->where(function ($q) {
                $q->whereNull('cii.is_invoice')
                  ->orWhere('cii.is_invoice', '!=', 1);
            });
    } else {
        $query->where('cii.id_developer', $developerId)
            ->whereNull('cii.submition_status')
            ->where(function ($q) {
                $q->whereNull('cii.is_invoice')
                  ->orWhere('cii.is_invoice', '!=', 1);
            });
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
        $developerId = auth()->user()->id;   // ← NEW
        $selectedIds = $request->input('selected_items', []);
        $selected_connection = $request->input('connection');

        if (empty($selectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No items selected.'
            ], 400);
        }

        // Fetch selected consolidate items
        $items = DB::table('consolidate_invoice_item')
            ->whereIn('id_invoice_item', $selectedIds)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice items found for submission.'
            ], 400);
        }

        // Find supplier (customer table)
        $customer = DB::table('customer')
            ->where('connection_integrate', $selected_connection)
            ->where('customer_type', 'SUPPLIER')
            ->whereNull('deleted')
            ->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found for selected connection.'
            ], 400);
        }

        $items = collect($items);
        $chunks = $items->chunk(25);

        $invoiceBaseNo = 'CONSOLIDATE-' . now()->format('Ymd-His');
        $version = 1;

        foreach ($chunks as $chunk) {

            // 🔥 Get sale_id_integrate from first item in chunk
            $saleId = $chunk->first()->sale_id_integrate;

            // Calculate total
            $total = $chunk->sum('line_extension_amount');
            $uniqueId = Str::uuid();
            $invoiceNo = $invoiceBaseNo . '-V' . $version;

            // -----------------------------------------------
            // INSERT INTO INVOICE (HEADER)
            // -----------------------------------------------
            $invoiceId = DB::table('invoice')->insertGetId([
                'unique_id' => $uniqueId,
                'sale_id_integrate' => $saleId,   // ← NEW (A)
                'connection_integrate' => $selected_connection,
                'invoice_status' => 'manual',
                'id_developer' => $developerId,  // ← NEW (C)
                'id_customer' => 6, 
                'id_supplier' => $customer->id_customer,
                'invoice_no' => $invoiceNo,
                'invoice_type_code' => '01',
                'issue_date' => now(),
                'tax_scheme_id' => 'OTH',
                'tax_category_id'=>'01',
                'price' => $total,
                'taxable_amount' => 0,
                'payment_note_term' => 'CASH',
                'tax_amount' => 0,
                'tax_percent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // -----------------------------------------------
            // INSERT INTO INVOICE_ITEM
            // -----------------------------------------------
            foreach ($chunk as $index => $item) {
                DB::table('invoice_item')->insert([
                    'id_developer' => $developerId,   // ← NEW (C)
                    'unique_id' => $uniqueId,
                    'issue_date' => $item->issue_date,
                    'connection_integrate' => $item->connection_integrate,
                    'sale_id_integrate' => $item->sale_id_integrate,  // already correct
                    'id_consolidate_invoice' => $item->id_consolidate_invoice,
                    'line_id' => $index + 1,
                    'id_invoice' => $invoiceId,
                    'id_customer' => $customer->id_customer,
                    'invoiced_quantity' => $item->invoiced_quantity,
                    'line_extension_amount' => $item->line_extension_amount,
                    'item_description' => $item->item_description,
                    'price_amount' => $item->price_amount,
                    'price_discount' => $item->price_discount,
                    'price_extension_amount' => $item->price_extension_amount,
                    'item_clasification_value' => '004',
                    'created_at' => now(),
                ]);
            }

            // Mark consolidate items as submitted
            DB::table('consolidate_invoice_item')
                ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
                ->update([
                    'submition_status' => 'submitted',
                    'is_invoice'       => 1,
                    'updated_at'       => now()
                ]);

            $version++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected items submitted as multiple invoices.'
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

        // 1. Validation: Check if invoice exists
        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice not found.');
        }

        // 2. Validation: Prevent deleting 'Submitted' invoices
        // We allow deleting if status is NULL, '', 'Pending', or 'Failed'
        $status = strtolower($invoice->submission_status ?? '');
        if ($status === 'submitted') {
            return redirect()->back()->with('error', 'Cannot delete an invoice that has been successfully submitted to LHDN.');
        }

        DB::beginTransaction();
        try {
            // 3. Reset Consolidation Items (Important!)
            // If this was a consolidated invoice, we must "release" the items back to the pool
            // so they can be selected again in the consolidation page.
            DB::table('consolidate_invoice_item')
                ->whereIn('id_consolidate_invoice', function($query) use ($id) {
                    $query->select('id_consolidate_invoice')
                          ->from('invoice_item')
                          ->where('id_invoice', $id);
                })
                ->update([
                    'submition_status' => null, // Clear submitted flag
                    'is_invoice' => null,       // Clear invoice flag
                    'updated_at' => now()
                ]);

            // 4. Delete Invoice Items
            DB::table('invoice_item')->where('id_invoice', $id)->delete();

            // 5. Delete Invoice Header
            DB::table('invoice')->where('id_invoice', $id)->delete();

            DB::commit();
            
            // --- UPDATED MESSAGE AS REQUESTED ---
            return redirect()->back()->with('success', 'Invoice deleted successfully. Items (if consolidated) are available again.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}