<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\eInvoisModel;
use Exception;

class ConsolidateListingController extends Controller
{
    /**
     * Display the Consolidated Invoice Listing
     */
    public function index(Request $request)
    {
        // 1. FILTER OPTIONS
        $customers = DB::table('customer')
            ->where('customer_type', 'SUPPLIER')
            ->orderBy('registration_name')
            ->get();

        // 2. MAIN QUERY
        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_supplier', '=', 'c.id_customer')
            ->leftJoin('connection_integrate AS ci', 'i.connection_integrate', '=', 'ci.code')
            ->select(
                'i.id_invoice',
                'i.invoice_no',
                'i.issue_date',
                'i.submission_status',
                'i.price',
                'i.uuid',
                'i.submission_uuid',
                'i.invoice_type_code',
                'c.registration_name', // Supplier Name
                'i.id_supplier',
                'i.connection_integrate',
                'ci.name AS connection_name'
            );

        // 3. CONSOLIDATED LOGIC (B2C Filter)
        $query->where(function($q) {
            $q->where('i.invoice_no', 'LIKE', 'CONSOLIDATE-%')
              ->orWhereNull('i.id_customer')
              ->orWhere('i.id_customer', 6);
        });

        // 4. APPLY OPTIONAL FILTERS
        if ($request->filled('start_date')) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            if ($request->status === 'Pending') {
                $query->where(function($q) {
                    $q->whereNull('i.submission_status')
                      ->orWhere('i.submission_status', '')
                      ->orWhere('i.submission_status', 'Pending');
                });
            } else {
                $query->where('i.submission_status', $request->status);
            }
        }

        if ($request->filled('connection_integrate') && $request->connection_integrate !== 'ALL') {
            $query->where('i.connection_integrate', $request->connection_integrate);
            Session::put('connection_integrate', $request->connection_integrate);
        }

        // 5. FETCH DATA
        $invoices = $query->orderBy('i.issue_date', 'desc')->get();

        return view('consolidate.consolidate_listing', compact('customers', 'invoices'));
    }

    /**
     * Handle Bulk Submission via AJAX
     */
    public function submitSelected(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['success' => false, 'message' => 'Invalid request type.'], 400);
        }

        $selectedIds = $request->input('invoices', []);
        
        if (empty($selectedIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
        }

        $invoices = DB::table('invoice')
            ->whereIn('id_invoice', $selectedIds)
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No valid invoices found.'], 400);
        }

        $conn = $request->connection_integrate ?? $invoices->first()->connection_integrate;
        Session::put('connection_integrate', $conn);

        $successCount = 0;
        $failCount = 0;

        foreach ($invoices as $inv) {
            try {
                session([
                    'invoice_type_code' => $inv->invoice_type_code ?? '01',
                    'invoice_unique_id' => $inv->unique_id,
                    'consolidate_status' => '1'
                ]);

                DB::table('invoice')->where('id_invoice', $inv->id_invoice)->update([
                    'submission_status' => 'Submitted',
                    'updated_at' => now()
                ]);

                $model = new eInvoisModel();
                $model->submit($inv->id_invoice);

                $successCount++;

            } catch (Exception $e) {
                $failCount++;
                DB::table('invoice')->where('id_invoice', $inv->id_invoice)->update([
                    'submission_status' => 'Failed',
                    'updated_at' => now()
                ]);
            }
        }

        $message = "Submitted: $successCount, Failed: $failCount";
        if ($failCount > 0) $message .= ". Please check failed invoices.";

        return response()->json([
            'success' => $failCount === 0, 
            'message' => $message,
            'connection_integrate' => session('connection_integrate')
        ], 200);
    }

    /**
     * Show Invoice Details
     * RENAMED from 'show' to 'showInvoice' to match your Route
     */
    public function showInvoice($id_supplier, $id_invoice)
    {
        // 1. Fetch Invoice
        $invoice = DB::table('invoice')
            ->where('id_invoice', $id_invoice)
            ->first();

        if (!$invoice) abort(404, "Invoice not found.");

        // 2. Fetch Supplier
        // Try using the passed ID first, fallback to invoice data
        $supplier = DB::table('customer')->where('id_customer', $id_supplier)->first();
        if (!$supplier) {
             $supplier = DB::table('customer')->where('id_customer', $invoice->id_supplier)->first();
        }

        // 3. Fetch Customer (Buyer)
        $customer = DB::table('customer')->where('id_customer', $invoice->id_customer)->first();
        
        // Mock B2C Customer if missing
        if (!$customer) {
            $customer = (object) [
                'registration_name' => 'General Public',
                'tin_no' => 'EI00000000010',
                'identification_no' => 'N/A',
                'address_line_1' => '',
                'address_line_2' => '',
                'city_name' => '',
                'postal_zone' => '',
                'country_code' => 'MYS',
                'phone' => ''
            ];
        }

        // 4. Fetch Items
        $items = DB::table('invoice_item')->where('id_invoice', $id_invoice)->get();
        
        // Legacy support using UUID
        if ($items->isEmpty() && !empty($invoice->unique_id)) {
            $items = DB::table('invoice_item')->where('unique_id', $invoice->unique_id)->get();
        }

        return view('consolidate.show_invoice', compact('invoice', 'customer', 'supplier', 'items'));
    }
}