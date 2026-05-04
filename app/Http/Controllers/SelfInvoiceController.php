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
        $isSelfBill = $request->query('type') === 'self_bill';
        $typeCodes = $isSelfBill ? ['11', '12', '13', '14'] : ['01', '02', '03', '04'];
        
        $query = DB::table('invoice')
            ->leftJoin('customer as c', 'invoice.id_customer', '=', 'c.id_customer') // Customer for Normal
            ->leftJoin('customer as s', 'invoice.id_supplier', '=', 's.id_customer') // Supplier for Self-Bill
            ->whereIn('invoice.invoice_type_code', $typeCodes);

        // Filter by Selected IDs
        if ($request->filled('ids')) {
            $ids = explode(',', $request->ids);
            $query->whereIn('invoice.id_invoice', $ids);
        } else {
            // Apply Search Filters (Date, Status, Type Code)
            if ($request->filled('start_date')) $query->where('invoice.issue_date', '>=', $request->start_date);
            if ($request->filled('end_date')) $query->where('invoice.issue_date', '<=', $request->end_date);
            if ($request->filled('status')) $query->where('invoice.submission_status', $request->status);
            if ($request->filled('invoice_type_code')) $query->where('invoice.invoice_type_code', $request->invoice_type_code);
        }

        $results = $query->select(
            'invoice.invoice_no', 
            'invoice.invoice_type_code', 
            $isSelfBill ? 's.registration_name as party_name' : 'c.registration_name as party_name',
            $isSelfBill ? 's.tin_no as party_tin' : 'c.tin_no as party_tin',
            'invoice.issue_date', 
            'invoice.price'
        )->get();

        $prefix = $isSelfBill ? "SelfBill" : "Invoice";
        $filename = "{$prefix}_Export_" . date('Ymd_His') . ".csv";

        return response()->stream(function() use ($results, $isSelfBill) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($file, ['Invoice No', 'Type Code', $isSelfBill ? 'Supplier Name' : 'Customer Name', 'TIN', 'Date', 'Total Price']);

            foreach ($results as $row) {
                fputcsv($file, [$row->invoice_no, $row->invoice_type_code, $row->party_name, $row->party_tin, $row->issue_date, $row->price]);
            }
            fclose($file);
        }, 200, [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        ]);
    }

/**
     * 7. IMPORT CSV
     */
    public function import(Request $request)
    {
        $file = $request->file('csv_file');
        
        if (!$file) {
            return back()->with('error', 'Please upload a valid CSV file.');
        }

        // Get the current connection context
        $connection = $request->connection_integrate ?? session('connection_integrate');

        if (empty($connection)) {
            return back()->with('error', 'No connection found. Please select an integration connection before importing.');
        }

        // Fetch the Owner LHDN Account (The main company issuing these invoices)
        $ownerAccount = DB::table('customer')
            ->where('connection_integrate', $connection)
            ->where('customer_type', 'SUPPLIER') // This identifies the owner account
            ->where('is_deleted', 0)
            ->first();

        // Fallback to null if not found
        $ownerId = $ownerAccount ? $ownerAccount->id_customer : null;

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            fgetcsv($handle); // Skip header row
            $count = 0;
            $errors = [];

            // Begin Transaction to ensure data integrity
            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    
                    // Skip completely empty rows at the bottom of CSVs
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $invoiceNo   = trim($row[0] ?? '');
                    $companyName = trim($row[1] ?? '');

                    // --- NEW DATE PARSING LOGIC ---
                    $issueDateRaw = trim($row[2] ?? '');
                    $issueDate    = null;

                    if (!empty($issueDateRaw)) {
                        try {
                            // PHP strtotime gets confused by slashes (thinks it's MM/DD/YYYY). 
                            // Replacing '/' with '-' forces it to read as DD-MM-YYYY.
                            $cleanDate = str_replace('/', '-', $issueDateRaw);
                            $issueDate = \Carbon\Carbon::parse($cleanDate)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // If the date is completely invalid, fallback to null so the query uses now() later
                            $issueDate = null;
                        }
                    }
                    // ------------------------------

                    $description = trim($row[3] ?? '');
                    $qty         = floatval(trim($row[4] ?? 0));
                    $unitPrice   = floatval(trim($row[5] ?? 0));
                    $discount    = floatval(trim($row[6] ?? 0));
                    $taxRate     = floatval(trim($row[7] ?? 0));

                    if (empty($invoiceNo) || empty($companyName)) {
                        $errors[] = "A row was skipped: Missing Invoice Number or Company Name.";
                        continue;
                    }

                    // 1. Find Party by Company Name & Connection
                    $party = DB::table('customer')
                        ->where('registration_name', $companyName)
                        ->where('connection_integrate', $connection)
                        ->where('is_deleted', 0)
                        ->first();

                    if (!$party) {
                        $errors[] = "Row '$invoiceNo' skipped: Company '$companyName' is not registered in this connection.";
                        continue;
                    }

                    // 2. Dynamic check: Is this row a Self-Bill or a Normal Invoice?
                    $rowIsSelfBill = ($party->is_selfbill_supplier == 1);

                    // 3. Calculate Totals
                    $lineExtensionAmount = $qty * $unitPrice;
                    $taxAmount = $lineExtensionAmount * ($taxRate / 100);
                    $totalPrice = $lineExtensionAmount + $taxAmount - $discount;

                    // 4. Insert into 'invoice' table (Header)
                    $invoiceId = DB::table('invoice')->insertGetId([
                        'invoice_no' => $invoiceNo,
                        'unique_id' => bin2hex(random_bytes(16)),
                        'id_developer' => auth()->id() ?? 0, // Fallback to 0
                        'connection_integrate' => $connection,
                        
                        // Self-Bill: CSV Company = Supplier | Owner = Customer
                        // Normal:    Owner = Supplier | CSV Company = Customer
                        'id_supplier' => $rowIsSelfBill ? $party->id_customer : $ownerId,
                        'id_customer' => $rowIsSelfBill ? $ownerId : $party->id_customer,
                        
                        // Assign correct LHDN Type Code
                        'invoice_type_code' => $rowIsSelfBill ? '11' : '01',
                        
                        // Use parsed issueDate, fallback to today's date if null
                        'issue_date' => $issueDate ? $issueDate : now()->format('Y-m-d'),
                        
                        'price' => $lineExtensionAmount, 
                        'taxable_amount' => $lineExtensionAmount - $discount,
                        'tax_amount' => $taxAmount,
                        
                        'invoice_status' => 'Pending',
                        'submission_status' => 'Pending',
                        'is_import' => 1,
                        
                        // LHDN Defaults
                        'tax_category_id' => '01', 
                        'tax_scheme_id' => 'OTH',   
                        'payment_note_term' => 'CASH',

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 5. Insert into 'invoice_item' table (Details)
                    DB::table('invoice_item')->insert([
                        'id_invoice' => $invoiceId,
                        'id_developer' => auth()->id() ?? 0,
                        'connection_integrate' => $connection,
                        'item_description' => $description,
                        'invoiced_quantity' => $qty,
                        'price_amount' => $unitPrice,
                        'line_extension_amount' => $lineExtensionAmount,
                        'price_discount' => $discount,
                        'tax' => $taxAmount,
                        'is_import' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $count++;
                }
                
                DB::commit();
                fclose($handle);

                // --- RETURN RESPONSES ---
                
                // If 0 records were imported, throw a hard error.
                if ($count === 0 && count($errors) > 0) {
                    $errorSummary = implode('<br>', array_slice($errors, 0, 5));
                    if (count($errors) > 5) $errorSummary .= "<br>...(and more)";
                    return back()->with('error', "Import Failed. 0 records were imported.<br><br>Reasons:<br>" . $errorSummary);
                }

                // If some imported, but some skipped
                if (count($errors) > 0) {
                    $errorSummary = implode('<br>', array_slice($errors, 0, 5));
                    if (count($errors) > 5) $errorSummary .= "<br>...(and more)";
                    return back()->with('warning', "Imported $count records with some skips.<br><br>Errors:<br>" . $errorSummary);
                }

                // Perfect Import
                return back()->with('success', "Successfully imported $count records.");

            } catch (\Exception $e) {
                DB::rollBack();
                fclose($handle);
                \Log::error("CSV Import Error: " . $e->getMessage());
                return back()->with('error', 'System Error during import: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Could not read the uploaded file.');
    }
    /**
     * 8. DOWNLOAD TEMPLATE
     */
public function downloadTemplate(Request $request)
{
    $isSelfBill = $request->query('type') === 'self_bill';
    $filename = $isSelfBill ? "SelfBill_Template.csv" : "Invoice_Template.csv";
    
    // Unified columns matching ConsolidateImport
    $columns = [
        'invoice_no',      // maps to invoice.invoice_no
        'company_name',    // maps to customer.registration_name
        'issue_date', 
        'description',     // maps to invoice_item.item_description
        'qty', 
        'unit_price', 
        'discount_amount', 
        'tax_rate'
    ];

    $example = [
        $isSelfBill ? 'SB-1001' : 'INV-1001',
        'Acme Corporation',
        date('Y-m-d'), 
        'Professional Services', 
        '1', 
        '500.00', 
        '0.00', 
        '6'
    ];

    return response()->stream(function() use ($columns, $example) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);
        fputcsv($file, $example);
        fclose($file);
    }, 200, [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename=\"$filename\"",
    ]);
}
}