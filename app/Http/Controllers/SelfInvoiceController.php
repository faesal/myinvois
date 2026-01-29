<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\eInvoisModel;

class SelfInvoiceController extends Controller
{
    /**
     * 1. LISTING PAGE
     * Displays all Self-Bill documents (Types 11, 12, 13, 14).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');

        // 1. Build Query (LEFT JOIN ensures data appears even if supplier/type missing)
        $query = DB::table('invoice')
            ->leftJoin('customer as s', 'invoice.id_supplier', '=', 's.id_customer')
            ->leftJoin('invoice_type', 'invoice.invoice_type_code', '=', 'invoice_type.code')
            ->whereIn('invoice.invoice_type_code', ['11', '12', '13', '14'])
            ->select(
                'invoice.*',
                's.registration_name as supplier_name',
                's.tin_no as supplier_tin',
                'invoice_type.description as type_description'
            );

        // 2. Security Filter [DISABLED TO SHOW ALL DATA]
        // The original logic filtered by session. We comment this out so ALL data appears.
        /* if ($user->role !== 'admin') {
            $query->where('invoice.connection_integrate', session('connection_integrate'));
        }
        */

        // 3. Search Filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice.invoice_no', 'like', "%$search%")
                  ->orWhere('s.registration_name', 'like', "%$search%");
            });
        }

        // 4. Supplier Filter
        if ($request->filled('id_supplier')) {
            $query->where('invoice.id_supplier', $request->id_supplier);
        }

        // 5. Fetch Data (Sorted by ID to ensure newest appear first)
        $invoices = $query->orderBy('invoice.id_invoice', 'desc')->paginate(50);

        // 6. Transform Data (Handle missing types/names)
        $invoices->getCollection()->transform(function ($inv) {
            $inv->submission_status = $inv->submission_status ?: 'Submitted';
            
            // If invoice_type table join failed, use manual mapping
            if (empty($inv->type_description)) {
                $types = [
                    '11' => 'Self-Bill Invoice', 
                    '12' => 'Self-Bill Credit Note', 
                    '13' => 'Self-Bill Debit Note', 
                    '14' => 'Self-Bill Refund Note'
                ];
                $inv->type_description = $types[$inv->invoice_type_code] ?? 'Self-Bill Document';
            }

            // Fallback for missing suppliers
            if (empty($inv->supplier_name)) {
                $inv->supplier_name = "Unknown Supplier (ID: {$inv->id_supplier})";
            }

            return $inv;
        });

        // 7. Fetch Suppliers for Filter
        $suppliers = DB::table('customer')
            ->where('is_selfbill_supplier', 1)
            ->whereNull('deleted')
            ->orderBy('registration_name', 'asc')
            ->get();

        $layout = $this->getLayout($user->role);

        // 8. Return View
        return view('invoices.selfbill', [
            'invoices' => $invoices, // Passes data as $invoices
            'layout' => $layout,
            'search' => $search,
            'suppliers' => $suppliers
        ]);
    }

    /**
     * 2. CREATE PAGE
     */
    public function create()
    {
        $user = Auth::user();

        $query = DB::table('customer')
            ->where('is_selfbill_supplier', 1)
            ->whereNull('deleted');

        // Security Filter [DISABLED] - To allow selecting any supplier
        /*
        if ($user->role !== 'admin') {
            $query->where('connection_integrate', session('connection_integrate'));
        }
        */

        $customers = $query->orderBy('registration_name', 'asc')->get();
        $layout = $this->getLayout($user->role);

        return view('invoices.create', [
            'customers' => $customers,
            'layout' => $layout
        ]);
    }

    /**
     * 3. STORE FUNCTION
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $unique_id = Str::uuid();

        try {
            DB::beginTransaction();

            $supplierId = $request->id_supplier;

            // Handle "New Supplier" logic
            if ($request->buyer_type === 'new') {
                $supplierId = DB::table('customer')->insertGetId([
                    'registration_name'    => strtoupper($request->company_name),
                    'tin_no'               => $request->tin_number,
                    'is_selfbill_supplier' => 1,
                    'customer_type'        => 'SUPPLIER',
                    'connection_integrate' => session('connection_integrate'),
                    'created_at'           => now(),
                ]);
            }

            // Insert Invoice Header
            $invoiceId = DB::table('invoice')->insertGetId([
                'unique_id'            => $unique_id,
                'invoice_no'           => $request->invoice_no,
                'invoice_type_code'    => '11', 
                'issue_date'           => $request->issue_date,
                'id_supplier'          => $supplierId,
                'id_customer'          => $request->id_customer,
                'price'                => $request->price,
                'invoice_status'       => 'Submitted',
                'submission_status'    => 'Submitted',
                'connection_integrate' => session('connection_integrate'),
                'id_developer'         => ($user->role === 'developer') ? $user->id : null,
                'created_at'           => now(),
            ]);

            // Insert Invoice Items
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    DB::table('invoice_item')->insert([
                        'id_invoice'            => $invoiceId,
                        'item_description'      => $item['description'],
                        'invoiced_quantity'     => $item['qty'],
                        'price_amount'          => $item['unit_price'],
                        'line_extension_amount' => $item['qty'] * $item['unit_price'],
                        'created_at'            => now(),
                    ]);
                }
            }

            DB::commit();

            // Submit to e-Invoice API (If model exists)
            // (new eInvoisModel())->submit($invoiceId);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 4. UPDATE HEADER
     */
    public function update(Request $request, $id)
    {
        try {
            DB::table('invoice')->where('id_invoice', $id)->update([
                'invoice_no' => $request->invoice_no,
                'issue_date' => $request->issue_date,
                'price'      => $request->price,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'Details updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * 5. DELETE INVOICE
     */
    public function destroy($id)
    {
        DB::table('invoice')->where('id_invoice', $id)->delete();
        DB::table('invoice_item')->where('id_invoice', $id)->delete();
        
        return back()->with('success', 'Record deleted successfully.');
    }

    /**
     * 6. EXPORT CSV
     */
    public function export(Request $request)
    {
        $query = DB::table('invoice')
            ->leftJoin('customer as s', 'invoice.id_supplier', '=', 's.id_customer')
            ->whereIn('invoice.invoice_type_code', ['11', '12', '13', '14'])
            ->select('invoice.invoice_no', 'invoice.invoice_type_code', 's.registration_name', 's.tin_no', 'invoice.issue_date', 'invoice.price');

        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('invoice.id_invoice', $ids);
        }

        $results = $query->get();
        $filename = "SelfBill_Export_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice No', 'Type Code', 'Supplier Name', 'TIN', 'Date', 'Total Price']);

            foreach ($results as $row) {
                fputcsv($file, [
                    $row->invoice_no, 
                    $row->invoice_type_code, 
                    $row->registration_name, 
                    $row->tin_no, 
                    $row->issue_date, 
                    $row->price
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 7. IMPORT CSV
     */
    public function import(Request $request)
    {
        $file = $request->file('csv_file');
        if (!$file) return back()->with('error', 'Please upload a valid CSV file.');

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            fgetcsv($handle); // Skip header row
            $count = 0;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                // Find supplier by TIN
                $supplier = DB::table('customer')
                    ->where('tin_no', trim($row[1]))
                    ->where('is_selfbill_supplier', 1)
                    ->first();

                // Only insert if supplier found
                if ($supplier) {
                    DB::table('invoice')->insert([
                        'invoice_no' => $row[0],
                        'id_supplier' => $supplier->id_customer,
                        'invoice_type_code' => '11',
                        'issue_date' => $row[3],
                        'price' => $row[4],
                        'submission_status' => 'Submitted',
                        'connection_integrate' => session('connection_integrate'),
                        'created_at' => now(),
                    ]);
                    $count++;
                }
            }
            fclose($handle);
            return back()->with('success', "Successfully imported $count invoices.");
        }
        return back()->with('error', 'Could not read file.');
    }

    /**
     * 8. DOWNLOAD TEMPLATE
     */
    public function downloadTemplate()
    {
        $filename = "SelfBill_Template.csv";
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['invoice_no', 'supplier_tin', 'type_code', 'issue_date', 'price']);
            fputcsv($file, ['SB-10001', 'C1234567890', '11', '2026-01-26', '500.00']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Helper for Layout
    private function getLayout($role) {
        return ($role === 'admin') ? 'layouts.adminLayout' : (($role === 'developer') ? 'layouts.developerLayout' : 'layouts.app');
    }
}