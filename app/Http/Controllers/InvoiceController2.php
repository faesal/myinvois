<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KLH\MyInvois\MyInvois;
use KLH\MyInvois\Models\Invoice;
use Klsheng\Myinvois\MyInvoisClient;
use Klsheng\Myinvois\Example\Ubl\CreateDocumentExample;
use Klsheng\Myinvois\Ubl\Constant\InvoiceTypeCodes;
use Klsheng\Myinvois\Helper\MyInvoisHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $prodMode;

    public function __construct()
    {
        $this->clientId = "68459bb8-ed45-4ea6-8846-5ba2740a5e2f";
        $this->clientSecret = "ed9d15f7-1886-48f7-b642-9d85ab995881";
        $this->prodMode = false;
    }

    private function getClient()
    {
        return new MyInvoisClient($this->clientId, $this->clientSecret, $this->prodMode);
    }
    public function submitRefundNote(Request $request)
    {
        $selectedItemIds = $request->input('selected_items');
    
        if (empty($selectedItemIds)) {
            return response()->json(['message' => 'No items selected.'], 400);
        }
    
        $items = DB::table('invoice_item')
            ->whereIn('id_invoice_item', $selectedItemIds)
            ->get();
    
        if ($items->isEmpty()) {
            return response()->json(['message' => 'No valid items found.'], 404);
        }
    
        $originalInvoice = DB::table('invoice')->where('id_invoice', $items[0]->id_invoice)->first();
    
        // Generate Refund Note UUID
        $refundNoteUUID = Str::uuid();
    
        // Create new refund note (invoice_type_code: 04)
        $refundInvoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $refundNoteUUID,
            'sale_id_integrate' => $originalInvoice->sale_id_integrate,
            'connection_integrate' => $originalInvoice->connection_integrate,
            'id_customer' => $originalInvoice->id_customer,
            'id_supplier' => $originalInvoice->id_supplier,
            'invoice_status' => 'pending',
            'invoice_type_code' => '04', // Refund Note
            'issue_date' => Carbon::now(),
            'price' => 0,
            'taxable_amount' => 0,
            'tax_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'invoice_no' => null,
            'original_document_reference' => $originalInvoice->invoice_no,
        ]);
    
        // Insert each item into refund note (amounts are negative)
        $totalRefund = 0;
    
        foreach ($items as $index => $item) {
            DB::table('invoice_item')->insert([
                'unique_id' => $refundNoteUUID,
                'id_invoice' => $refundInvoiceId,
                'sale_id_integrate' => $item->sale_id_integrate,
                'connection_integrate' => $item->connection_integrate,
                'id_customer' => $item->id_customer,
                'line_id' => $index + 1,
                'invoiced_quantity' => $item->invoiced_quantity,
                'line_extension_amount' => -abs($item->line_extension_amount),
                'item_description' => $item->item_description,
                'price_amount' => -abs($item->price_amount),
                'price_discount' => $item->price_discount,
                'price_extension_amount' => -abs($item->price_extension_amount),
                'item_clasification_type' => $item->item_clasification_type,
                'item_clasification_value' => $item->item_clasification_value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            $totalRefund += $item->price_amount;
        }
    
        // Update total amount in refund invoice
        DB::table('invoice')->where('id_invoice', $refundInvoiceId)->update([
            'price' => -abs($totalRefund),
            'taxable_amount' => -abs($totalRefund),
            'updated_at' => now()
        ]);
    
        return response()->json(['message' => '✅ Refund Note submitted successfully.']);
    }
    
    public function submitDebitNote(Request $request)
    {
        $selectedItemIds = $request->input('selected_items');
    
        if (empty($selectedItemIds)) {
            return response()->json(['message' => 'No items selected.'], 400);
        }
    
        $items = DB::table('invoice_item')
            ->whereIn('id_invoice_item', $selectedItemIds)
            ->get();
    
        if ($items->isEmpty()) {
            return response()->json(['message' => 'No valid items found.'], 404);
        }
    
        $originalInvoice = DB::table('invoice')->where('id_invoice', $items[0]->id_invoice)->first();
    
        // Generate unique ID for debit note
        $debitNoteUUID = Str::uuid();
    
        // Create new invoice as debit note
        $debitNoteId = DB::table('invoice')->insertGetId([
            'unique_id' => $debitNoteUUID,
            'sale_id_integrate' => $originalInvoice->sale_id_integrate,
            'connection_integrate' => $originalInvoice->connection_integrate,
            'id_customer' => $originalInvoice->id_customer,
            'id_supplier' => $originalInvoice->id_supplier,
            'invoice_status' => 'pending',
            'invoice_type_code' => '03', // 03 = Debit Note
            'issue_date' => now(),
            'price' => 0,
            'taxable_amount' => 0,
            'tax_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'invoice_no' => null,
            'original_document_reference' => $originalInvoice->invoice_no,
        ]);
    
        // Insert each selected item (positive value)
        $total = 0;
        foreach ($items as $index => $item) {
            DB::table('invoice_item')->insert([
                'unique_id' => $debitNoteUUID,
                'id_invoice' => $debitNoteId,
                'sale_id_integrate' => $item->sale_id_integrate,
                'connection_integrate' => $item->connection_integrate,
                'id_customer' => $item->id_customer,
                'line_id' => $index + 1,
                'invoiced_quantity' => $item->invoiced_quantity,
                'line_extension_amount' => abs($item->line_extension_amount),
                'item_description' => $item->item_description,
                'price_amount' => abs($item->price_amount),
                'price_discount' => $item->price_discount,
                'price_extension_amount' => abs($item->price_extension_amount),
                'item_clasification_type' => $item->item_clasification_type,
                'item_clasification_value' => $item->item_clasification_value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            $total += $item->price_amount;
        }
    
        // Update totals in debit note invoice
        DB::table('invoice')->where('id_invoice', $debitNoteId)->update([
            'price' => abs($total),
            'taxable_amount' => abs($total),
            'updated_at' => now()
        ]);
    
        return response()->json(['message' => '✅ Debit Note submitted successfully.']);
    }
    

    public function submitCreditNote(Request $request)
    {
        $selectedItemIds = $request->input('selected_items');

        if (empty($selectedItemIds)) {
            return response()->json(['message' => 'No items selected.'], 400);
        }

        $items = DB::table('invoice_item')
            ->whereIn('id_invoice_item', $selectedItemIds)
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => 'No valid items found.'], 404);
        }

        // Ambil invoice asal dari salah satu item (anda boleh guna join juga jika perlu)
        $originalInvoice = DB::table('invoice')->where('id_invoice', $items[0]->id_invoice)->first();

        // Buat unique_id untuk credit note
        $creditNoteUUID = Str::uuid();

        // Masuk ke table invoice sebagai credit note
        $creditNoteId = DB::table('invoice')->insertGetId([
            'unique_id' => $creditNoteUUID,
            'sale_id_integrate' => $originalInvoice->sale_id_integrate,
            'connection_integrate' => $originalInvoice->connection_integrate,
            'id_customer' => $originalInvoice->id_customer,
            'id_supplier' => $originalInvoice->id_supplier,
            'invoice_status' => 'pending',
            'invoice_type_code' => '02', // 02 = Credit Note
            'issue_date' => now(),
            'price' => 0, // Diisi selepas loop
            'taxable_amount' => 0,
            'tax_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'invoice_no' => null,
            'original_document_reference' => $originalInvoice->invoice_no,
        ]);

        // Simpan item
        $total = 0;
        foreach ($items as $index => $item) {
            DB::table('invoice_item')->insert([
                'unique_id' => $creditNoteUUID,
                'id_invoice' => $creditNoteId,
                'sale_id_integrate' => $item->sale_id_integrate,
                'connection_integrate' => $item->connection_integrate,
                'id_customer' => $item->id_customer,
                'line_id' => $index + 1,
                'invoiced_quantity' => $item->invoiced_quantity, // boleh tolak atau positif
                'line_extension_amount' => -abs($item->line_extension_amount), // negatif
                'item_description' => $item->item_description,
                'price_amount' => -abs($item->price_amount),
                'price_discount' => $item->price_discount,
                'price_extension_amount' => -abs($item->price_extension_amount),
                'item_clasification_type' => $item->item_clasification_type,
                'item_clasification_value' => $item->item_clasification_value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $total += $item->price_amount;
        }

        // Update amount pada invoice
        DB::table('invoice')->where('id_invoice', $creditNoteId)->update([
            'price' => -abs($total),
            'taxable_amount' => -abs($total), // adjust ikut logic cukai
            'updated_at' => now()
        ]);

        return response()->json(['message' => '✅ Credit Note created successfully.']);
    }

    public function qr_link($uuid){

        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
         // Submit or fetch your document (use your existing invoice UUID)
        // Example: retrieving an existing document using its UUID
    
        $response = $client->getDocument($uuid);
        
        // Extract the Long ID from the response
        $longId = $response['longID'] ?? null;
       

        // Determine the base URL
       // ? 'https://myinvois.hasil.gov.my'
       // : 'https://preprod.myinvois.hasil.gov.my';

        $base = 'https://preprod.myinvois.hasil.gov.my';
           
        // Construct the shareable link
        echo $link = "{$base}/{$uuid}/share/{$longId}";
        exit();
    }

    public function login()
    {
        $client = $this->getClient();
        $client->login('IG20868489010');
        $access_token = $client->getAccessToken();
        // Store $access_token somewhere to re-use it again within 1 hour

        // OR
        $client->setAccessToken('access_token');
        $client->setOnbehalfof('IG20868489010');
    }

    public function selectItems(Request $request)
{
    $start = $request->input('start_date', now()->startOfMonth()->toDateString());
    $end = $request->input('end_date', now()->endOfMonth()->toDateString());

    session(['consolidate_start' => $start]);
    session(['consolidate_end' => $end]);

    $selectedConnection = $request->input('connection');

    $query = DB::table('consolidate_invoice_item')
        ->whereBetween('issue_date', [$start, $end]);

    if ($selectedConnection) {
        $query->where('connection_integrate', $selectedConnection);
    }
    $query->where('submition_status','!=','submitted');
    $items = $query->orderBy('issue_date')->get();

    // Optional: get list of available connections from DB
    $availableConnections = DB::table('consolidate_invoice_item')
        ->select('connection_integrate')
        ->distinct()
        ->pluck('connection_integrate')
        ->toArray();

    return view('consolidate.select', compact('items', 'start', 'end', 'availableConnections'));
}


    public function submitSelected(Request $request)
    {
        $selectedIds = $request->input('selected_items', []);
        $selected_connection=$request->input('selected_connection');
        
        if (empty($selectedIds)) {
            return back()->with('error', 'No items selected.');
        }

        $items = DB::table('consolidate_invoice_item')
            ->whereIn('id_invoice_item', $selectedIds)
            ->get();

 
        $total = $items->sum('line_extension_amount');
        

        $uniqueId = Str::uuid();
        $invoiceNo = 'MANUAL-' . now()->format('YmdHis');
        $id_supplier=7;
        $invoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $uniqueId,
            'connection_integrate' =>  $selected_connection,
            'invoice_status' => 'manual',
            'id_supplier'=>$id_supplier,
            'invoice_no' => $invoiceNo,
            'invoice_type_code' => '01',
            'issue_date' => now(),
            'price' => $total,
            'taxable_amount' => 0,
            'payment_note_term'=>'Cash',
            'tax_amount' => 0,
            'tax_percent' => '6',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $index => $item) {
            DB::table('invoice_item')->insert([
                'unique_id' => $uniqueId,
                'issue_date' => $item->issue_date,
                'connection_integrate' => $item->connection_integrate,
                'sale_id_integrate' => $item->sale_id_integrate,
                'id_consolidate_invoice' => $item->id_consolidate_invoice,
                'line_id' => $index + 1,
                'id_invoice'=>$invoiceId,
                'invoiced_quantity' => $item->invoiced_quantity,
                'line_extension_amount' => $item->line_extension_amount,
                'item_description' => $item->item_description,
                'price_amount' => $item->price_amount,
                'price_discount' => $item->price_discount,
                'price_extension_amount' => $item->price_extension_amount,
                'item_clasification_value' => $item->item_clasification_value,
                'created_at' => now()
            ]);
        }

        DB::table('consolidate_invoice_item')
        ->whereIn('id_invoice_item', $selectedIds)
        ->update([
            'submition_status' => 'submitted',
            'updated_at' => now()
        ]);

        session(['invoice_unique_id' => $uniqueId]);
        session(['consolidate_status' => 1]);
        session(['invoice_id' => $invoiceNo]);
        $this->submit($invoiceId);
        return redirect()->route('consolidate.select')->with('success', 'Selected items moved to Invoice.');
    }


    public function show_invoice($id_supplier,$id_customer,$id_invoice)
    {
    
    $invoice = $record = DB::table('invoice')->where('id_invoice', $id_invoice)->first();
    $supplier = DB::table('customer')->where('id_customer', $id_supplier)->first(); // Adjust ID as needed
    $customer = DB::table('customer')->where('id_customer', $id_customer)->first(); // Adjust ID as needed
    $items = DB::table('invoice_item')->where('id_invoice', $id_invoice)->get();
 

    // Generate PDF
    //$pdf = PDF::loadView('invoices.show', compact('invoice', 'customer', 'items'));

    // Save PDF temporarily
    //$pdfPath = storage_path("app/public/invoice_{$invoice->invoice_no}.pdf");
   // $pdf->save($pdfPath);
    
    // Send Email
    
    

    return view('invoices.show', compact('invoice', 'customer','supplier', 'items'))
        ->with('success', 'Invoice sent to customer.');
    }

    public function listing_submission(Request $request){
        $query = DB::table('invoice')
            ->leftJoin('customer', 'invoice.id_customer', '=', 'customer.id_customer')
            ->select(
                'invoice.invoice_no',
                'invoice.id_supplier',
                'invoice.id_customer',
                'invoice.id_invoice',
                'invoice.issue_date',
                'invoice.price',
                'invoice.invoice_status',
                'customer.registration_name as customer_name'
            );

        if ($request->filled('start_date')) {
            $query->whereDate('invoice.issue_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('invoice.issue_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('invoice.invoice_status', $request->status);
        }

        $invoices = $query->orderBy('invoice.issue_date', 'desc')->get();

        return view('invoices.submission', compact('invoices'));
    }

    public function syncFromPOS(Request $request)
    {
        $pos = $request->query('pos'); // e.g., bill
        $sale_id = $request->query('sale_id');

        if (!is_numeric($sale_id)) {
            return response()->json(['error' => 'Invalid sale_id.'], 400);
        }
        // Define config untuk POS
        $dbConfigs = [
            'bill' => [
                'host' => '127.0.0.1',
                'database' => 'liveapps_bill',
                'username' => 'liveapps_bill',
                'password' => 'b0sZFs1MHq#d',
            ],
            'sass' => [
                'host' => '127.0.0.1',
                'database' => 'liveapps_saas_pos',
                'username' => 'liveapps_saas_pos',
                'password' => '(!{0hu1;g7?Z',
            ],
        ];

        if (!isset($dbConfigs[$pos])) {
            return response()->json(['error' => 'Invalid POS config'], 400);
        }

        // Semak jika sudah wujud dalam invoice (elak duplicate)
        $existing = DB::table('invoice')
            ->where('sale_id_integrate', $sale_id)
            ->where('connection_integrate', $pos)
            ->first();

        if ($existing) {
            return Redirect::to("https://mysynctax.com/einvoice/public/createcustomer/{$existing->unique_id}");
        }

        // Set dynamic POS DB connection
        Config::set("database.connections.pos_connection", array_merge(
            config('database.connections.pos_connection'),
            $dbConfigs[$pos]
        ));

        try {
            $id_supplier = DB::table('customer')
                ->where('connection_integrate', $pos)
                ->value('id_customer');

            if (!$id_supplier) {
                return response()->json(['error' => 'Supplier not found for the given POS.'], 404);
            }

            session(['id_supplier' => $id_supplier]);
            // Ambil sale info
            $sale = DB::connection('pos_connection')->table('phppos_sales')->where('sale_id', $sale_id)->first();

   
            // JOIN dengan items untuk dapat nama item
            $items = DB::connection('pos_connection')
                ->table('phppos_sales_items as si')
                ->join('phppos_items as i', 'si.item_id', '=', 'i.item_id')
                ->where('si.sale_id', $sale_id)
                ->select('si.*', 'i.name as item_name')
                ->get();

           
        } catch (\Exception $e) {
            //echo $e->getMessage();
            //exit();
            return response()->json(['error' => 'Failed to connect POS DB', 'details' => $e->getMessage()], 500);
        }

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        DB::beginTransaction();
        try {
            $unique_id=strtoupper(bin2hex(random_bytes(8)));
            // Insert invoice dengan sale_id_integrate dan connection_integrate
            $invoice_id = DB::table('invoice')->insertGetId([
                'invoice_no' => $sale_id,
                'unique_id' =>  $unique_id,
                'sale_id_integrate'=> $sale_id ,
                'connection_integrate'=> $pos,
                'id_supplier' => $id_supplier ?? 0,
                'invoice_status' => 'Valid',
                'invoice_type_code' => '01',
                'tax_category_id' => '01',
                'tax_exemption_reason' => '',
                'tax_scheme_id' => 'OTH',
                'payment_note_term' => 'CASH',
                'payment_financial_account' => '-',
                'issue_date' => $sale->sale_time,
                'price' => $sale->total,
                'taxable_amount' => $sale->subtotal,
                'tax_amount' => $sale->tax,
                'tax_percent' => 0,
                'payment_method' => $sale->payment_type ?? 'Cash',
                'sale_id_integrate' => $sale_id,
                'connection_integrate' => $pos,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert items
            foreach ($items as $item) {
                try {
                    DB::table('invoice_item')->insert([
                        'id_invoice' => $invoice_id,
                        'sale_id_integrate'=> $sale_id ,
                        'connection_integrate'=> $pos,
                        'unique_id' =>  $unique_id,
                        'line_id' => $item->line,
                        'invoiced_quantity' => $item->quantity_purchased,
                        'line_extension_amount' => $item->total,
                        'item_description' => $item->item_name ?? 'Unnamed Item',
                        'price_amount' => $item->subtotal,
                        'price_discount' => $item->discount_percent,
                        'price_extension_amount' => $item->subtotal,
                        'item_clasification_type' => null,
                        'item_clasification_value' => null,
                    ]);
                } catch (\Exception $e) {
                    dd('DB Error:', $e->getMessage());
                }
            }
            session(['invoice_unique_id' => $unique_id]);
            DB::commit();
           
            return Redirect::to("https://mysynctax.com/einvoice/public/createcustomer/$unique_id");

        } catch (\Exception $e) {
            DB::rollBack();
            echo $e->getMessage();
            exit();
            return response()->json(['error' => 'Failed to sync invoice', 'details' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
    $file = $request->file('file');

    if (!$file || !$file->isValid()) {
        return back()->withErrors(['msg' => 'Fail tidak sah']);
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
    $invoiceSheet = $spreadsheet->getSheetByName('invoice');
    $invoiceItemSheet = $spreadsheet->getSheetByName('invoice_item');

    $invoices = $invoiceSheet->toArray(null, true, true, true);
    $items = $invoiceItemSheet->toArray(null, true, true, true);

    // Kumpul invoice_no yang ada dalam invoice_item
    $invoiceNosInItem = collect(array_slice($items, 1))->pluck('A')->map(function ($v) {
        return trim($v);
    })->unique()->toArray();

    $errors = [];

    DB::beginTransaction();
    try {
        foreach (array_slice($invoices, 1) as $rowIndex => $row) {
            $invoice_no = trim($row['A']);
            $customer_name = trim($row['B']);
            $id_supplier = $row['C'];
            $issue_date = $row['D'];
            $price = $row['E'];
            $taxable_amount = $row['F'];
            $tax_amount = $row['G'];
            $payment_method = $row['H'];

            // ✅ Validate: invoice has matching items
            if (!in_array($invoice_no, $invoiceNosInItem)) {
                $errors[] = "❌ Baris " . ($rowIndex + 2) . ": Invoice '$invoice_no' tiada item.";
                continue; // skip insert
            }

            // ✅ Check or auto-create customer
            $customer = DB::table('customers')->where('customer_name', $customer_name)->first();
            if (!$customer) {
                // Auto-insert customer
                $id_customer = DB::table('customers')->insertGetId([
                    'customer_name' => $customer_name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $id_customer = $customer->id_customer;
            }

            // ✅ Insert invoice
            $id_invoice = DB::table('invoice')->insertGetId([
                'invoice_no' => $invoice_no,
                'id_customer' => $id_customer,
                'id_supplier' => $id_supplier,
                'issue_date' => $issue_date,
                'price' => $price,
                'taxable_amount' => $taxable_amount,
                'tax_amount' => $tax_amount,
                'payment_method' => $payment_method,
                'invoice_status' => 'Valid',
                'invoice_type_code' => '01',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ✅ Insert invoice items
            foreach (array_slice($items, 1) as $item) {
                if (trim($item['A']) === $invoice_no) {
                    DB::table('invoice_item')->insert([
                        'id_invoice' => $id_invoice,
                        'id_customer' => $id_customer,
                        'line_id' => $item['B'],
                        'invoiced_quantity' => $item['C'],
                        'item_description' => $item['D'],
                        'price_amount' => $item['E'],
                        'price_discount' => $item['F'],
                        'price_extension_amount' => $item['G']
                    ]);
                }
            }
        }

        DB::commit();

        if (count($errors) > 0) {
            return back()->with('partial_success', 'Import sebahagian berjaya.')->withErrors($errors);
        } else {
            return back()->with('success', 'Import berjaya sepenuhnya.');
        }

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['msg' => 'Gagal import sepenuhnya: ' . $e->getMessage()]);
    }
    }

    public function export()
    {
        $customers = DB::table('customers')->select('id_customer', 'customer_name')->get();

        $spreadsheet = new Spreadsheet();

        // Sheet: customer_list
        $customerSheet = $spreadsheet->getActiveSheet();
        $customerSheet->setTitle('customer_list');
        $customerSheet->fromArray(['id_customer', 'customer_name'], null, 'A1');

        foreach ($customers as $index => $customer) {
            $row = $index + 2;
            $customerSheet->setCellValue("A$row", $customer->id_customer);
            $customerSheet->setCellValue("B$row", $customer->customer_name);
        }

        // Sheet: invoice
        $invoiceSheet = $spreadsheet->createSheet();
        $invoiceSheet->setTitle('invoice');
        $invoiceSheet->fromArray([
            'invoice_no', 'id_customer', 'id_supplier', 'issue_date',
            'price', 'taxable_amount', 'tax_amount', 'payment_method'
        ], null, 'A1');

        // Apply dropdown to B2 (id_customer)
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('=customer_list!$B$2:$B$100');

        $invoiceSheet->setCellValue('B2', '');
        $invoiceSheet->getCell('B2')->setDataValidation($validation);

        // Sheet: invoice_item
        $itemSheet = $spreadsheet->createSheet();
        $itemSheet->setTitle('invoice_item');
        $itemSheet->fromArray([
            'invoice_no', 'line_id', 'invoiced_quantity', 'item_description',
            'price_amount', 'price_discount', 'price_extension_amount'
        ], null, 'A1');

        // Apply dropdown to invoice_no in itemSheet (A2)
        $invoiceDropdown = new DataValidation();
        $invoiceDropdown->setType(DataValidation::TYPE_LIST);
        $invoiceDropdown->setErrorStyle(DataValidation::STYLE_STOP);
        $invoiceDropdown->setAllowBlank(false);
        $invoiceDropdown->setShowDropDown(true);
        $invoiceDropdown->setFormula1('=invoice!$A$2:$A$100');

        $itemSheet->setCellValue('A2', '');
        $itemSheet->getCell('A2')->setDataValidation($invoiceDropdown);

        // Set active sheet back to invoice
        $spreadsheet->setActiveSheetIndexByName('invoice');

        // Output Excel
        $filename = 'invoice_template.xlsx';
        $writer = new Xlsx($spreadsheet);

        // Stream download
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }



    public function store(Request $request)
    {
    $invoice_no = $request->invoice_no;
    $id_customer = $request->id_customer;

    // Check if invoice already exists
    $invoice = DB::table('invoice')
        ->where('invoice_no', $invoice_no)
        ->where('id_customer', $id_customer)
        ->first();

    $invoiceData = [
        'id_customer' => $id_customer,
        'id_supplier' => $request->id_supplier,
        'invoice_status' => $request->invoice_status ?? 'Valid',
        'invoice_type_code' => $request->invoice_type_code ?? '01',
        'issue_date' => $request->issue_date ?? now(),
        'price' => $request->price,
        'taxable_amount' => $request->taxable_amount,
        'tax_amount' => $request->tax_amount,
        'tax_category_id' => $request->tax_category_id,
        'tax_exemption_reason' => $request->tax_exemption_reason,
        'tax_scheme_id' => $request->tax_scheme_id,
        'tax_percent' => $request->tax_percent,
        'payment_note_term' => $request->payment_note_term,
        'payment_financial_account' => $request->payment_financial_account,
        'include_signature' => $request->include_signature ?? 0,
        'uuid' => $request->uuid ?? (string) Str::uuid(),
        'submission_uuid' => $request->submission_uuid ?? (string) Str::uuid(),
        'long_id' => $request->long_id ?? 1,
        'payment_method' => $request->payment_method,
        'updated_at' => now()
    ];

    DB::beginTransaction();

    try {
        if ($invoice) {
            // Update if exists
            DB::table('invoice')
                ->where('id_invoice', $invoice->id_invoice)
                ->update($invoiceData);

            $invoice_id = $invoice->id_invoice;

            // Delete old items
            DB::table('invoice_item')
                ->where('id_invoice', $invoice_id)
                ->delete();
        } else {
            // Add extra fields for insert
            $invoiceData['invoice_no'] = $invoice_no;
            $invoiceData['created_at'] = now();

            // Insert and get ID
            $invoice_id = DB::table('invoice')->insertGetId($invoiceData);
        }

        // Insert items
        foreach ($request->items as $item) {
            DB::table('invoice_item')->insert([
                'id_invoice' => $invoice_id,
                'id_customer' => $id_customer,
                'line_id' => $item['line_id'],
                'invoiced_quantity' => $item['invoiced_quantity'],
                'line_extension_amount' => $item['line_extension_amount'],
                'item_description' => $item['item_description'],
                'price_amount' => $item['price_amount'],
                'price_discount' => $item['price_discount'],
                'price_extension_amount' => $item['price_extension_amount'] ?? null,
                'item_clasification_type' => $item['item_clasification_type'] ?? null,
                'item_clasification_value' => $item['item_clasification_value'] ?? null,
            ]);
        }

        DB::commit();

        return response()->json(['message' => 'Invoice saved successfully', 'invoice_id' => $invoice_id]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
    }
    }


    public function show($id)
    {
    
    $session = session('invoice_unique_id');
    $id_supplier=session('id_supplier');

    $this->submit($id);

    $invoice = $record = DB::table('invoice')->where('unique_id', $session)->first();
    $supplier = DB::table('customer')->where('id_customer', $id_supplier)->first(); // Adjust ID as needed
    $customer = DB::table('customer')->where('id_customer', $id)->first(); // Adjust ID as needed
    $items = DB::table('invoice_item')->where('unique_id', $session)->get();


    // Generate PDF
    //$pdf = PDF::loadView('invoices.show', compact('invoice', 'customer', 'items'));

    // Save PDF temporarily
    $pdfPath = storage_path("app/public/invoice_{$invoice->invoice_no}.pdf");
   // $pdf->save($pdfPath);
    
    // Send Email
    Mail::to($customer->email)->send((new InvoiceSent($invoice, $customer, $items,$supplier )));
    

    return view('emails.sent', compact('invoice', 'customer','supplier', 'items'))
        ->with('success', 'Invoice sent to customer.');
    }

    public function presubmit($id)
    {
        $session = session('invoice_unique_id');
        $id_supplier = session('id_supplier');

        // Update invoice record to assign customer
        DB::table('invoice')
            ->where('unique_id', $session)
            ->update(['id_customer' => $id]);

            DB::table('invoice_item')
            ->where('unique_id', $session)
            ->update(['id_customer' => $id]);

        // Fetch updated records
        $invoice = DB::table('invoice')->where('unique_id', $session)->first();
        $supplier = DB::table('customer')->where('id_customer', $id_supplier)->first();
        $customer = DB::table('customer')->where('id_customer', $id)->first();
        $items = DB::table('invoice_item')->where('unique_id', $session)->get();

        return view('invoices.invoice', compact('invoice', 'supplier', 'customer', 'items'))
            ->with('success', 'Invoice sent to customer.');
    }


    public function submit($id_customer)
    {

 
        try {

            $client = $this->getClient();
            $client->login();
            $access_token = $client->getAccessToken();
            $client->setAccessToken($access_token);
    
            $id = 'INV20240418105410';
            
            // ... existing supplier, customer, delivery, and data setup code ...
    
            // Verify certificate existence and permissions
            //$certPath = base_path('cert/certificate.crt');
            $certPath = base_path('cert/certificate.crt');
            $privatePath = base_path('cert/private.key');
            
            if (!file_exists($certPath) || !file_exists($privatePath)) {
                throw new \Exception("Certificate files not found");
            }
    
            // Verify certificate permissions
            if (!is_readable($certPath) || !is_readable($privatePath)) {
                throw new \Exception("Certificate files are not readable");
            }
    
            // Verify certificate validity
            $cert = openssl_x509_read(file_get_contents($certPath));
            if (!$cert) {
                throw new \Exception("Invalid certificate format");
            }
    
            // Check certificate expiration
            $certInfo = openssl_x509_parse($cert);
            if ($certInfo['validTo_time_t'] < time()) {
                throw new \Exception("Certificate has expired");
            }
    
            // Verify private key matches certificate
            $privateKey = openssl_pkey_get_private(file_get_contents($privatePath), 'Ks5#4de0');
            if (!$privateKey) {
                throw new \Exception("Invalid private key or passphrase");
            }
    
            // Verify key pair matches
            if (!openssl_x509_check_private_key($cert, $privateKey)) {
                throw new \Exception("Certificate and private key do not match");
            }
    
            $id = 'INV20240418105410';

            session(['invoice_id' => '']);
            session(['invoice_unique_id' => '']);
            
            $session = session('invoice_unique_id');
            echo $consolidate_status = session('consolidate_status');
            
            $record = DB::table('invoice')->where('unique_id', $session)->first();
            session(['invoice_id' => $record->invoice_no]);
            $data = [
                'id_invoice' => $record->id_invoice,
                'invoice_status' => $record->invoice_status,
                'invoice_no' => $record->invoice_no,
                'invoice_type_code' => '11',
                'issue_date' => $record->issue_date,
                'price' => $record->price,
                'taxable_amount' => $record->taxable_amount,
                'tax_amount' => $record->tax_amount,
                'tax_category_id' => $record->tax_category_id,
                'tax_exemption_reason' => $record->tax_exemption_reason,
                'tax_scheme_id' => $record->tax_scheme_id,
                'tax_percent' => $record->tax_percent,
                'payment_note_term' => $record->payment_note_term,
                'payment_financial_account' => $record->payment_financial_account,
                'include_signature' => $record->include_signature,
                'uuid' => $record->uuid,
                'long_id' => $record->long_id,
                'payment_method' => $record->payment_method,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ];
        
            if(empty($record->id_customer)){
                $customer=8;
            }else{
                $customer=$record->id_customer;
            }
   
            $supplierCustomer = DB::table('customer')->where('id_customer', $record->id_supplier)->first(); // Adjust ID as needed
         
            // 2. Transform DB record into array
            $supplier = [
                'tin_no' => $supplierCustomer->tin_no,
                'NRIC' => $supplierCustomer->identification_no,
                'BRN' => $supplierCustomer->sst_registration,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];
        
            $supplierCustomer = DB::table('customer')->where('id_customer', $customer)->first(); // Adjust ID as needed
          
            // 2. Transform DB record into array
            $customer = [
                'tin_no' => $supplierCustomer->tin_no,
                'sst_registration' => $supplierCustomer->sst_registration,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];
        
            $supplierCustomer = DB::table('customer')->where('id_customer',$record->id_supplier)->first(); // Adjust ID as needed
           
            $delivery = [
                'tin_no' => $supplierCustomer->tin_no,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];

            $invoiceItems = DB::table('invoice_item')->where('unique_id', $session)->get();
            print_r($invoiceItems);
            $items = [];
            
            foreach ($invoiceItems as $row) {
               // echo $item->id_invoice_item;
                $items[] = [
                    'id_invoice_item' => $row->id_invoice_item,
                    'id_customer' => $row->id_customer,
                    'id_invoice' => $row->id_invoice,
                    'price_discount' => $row->price_discount,
                    'line_id' => $row->line_id,
                    'invoiced_quantity' => $row->invoiced_quantity,
                    'line_extension_amount' => $row->line_extension_amount,
                    'item_description' => $row->item_description,
                    'price_amount' => $row->price_amount,
                    'price_extension_amount' => $row->price_extension_amount,
                    'item_clasification_value'=>$row->item_clasification_value
                ];
            }
      
            $data['items'] = $items;
            if($consolidate_status==1){
            $delivery='';
            }
            
            /*case InvoiceTypeCodes::CREDIT_NOTE:
                return new CreditNote();
                break;
            case InvoiceTypeCodes::DEBIT_NOTE:
                return new DebitNote();
                break;
            case InvoiceTypeCodes::REFUND_NOTE:
                return new RefundNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_INVOICE:
                return new SelfBilledInvoice();
                break;
            case InvoiceTypeCodes::SELF_BILLED_CREDIT_NOTE:
                return new SelfBilledCreditNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_DEBIT_NOTE:
                return new SelfBilledDebitNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_REFUND_NOTE:
                return new SelfBilledRefundNote();
                break;
            default:
                return new Invoice();
                break;*/


            $example = new CreateDocumentExample();
            $invoice = $example->createJsonDocument(
                InvoiceTypeCodes::INVOICE,
                $id,
                $supplier,
                $customer,
                $delivery,
                true,
                $certPath,
                $privatePath,
                false,
                [
                    'SigningTime' => date('Y-m-d\TH:i:s\Z'),
                    'DigestMethod' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                    'SignatureMethod' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
                ],
                $data
            );
       
            $documents = [];
            $document = MyInvoisHelper::getSubmitDocument($id, $invoice);
            $documents[] = $document;
            //echo $document;
            // echo hash('sha256', $invoice);
            print_r($invoice);
            //exit();
            //echo $invoice;

            $response = $client->submitDocument($documents);
            print_r($response);
            session(['consolidate_status' => '']);
            session(['invoice_id' => '']);
            session(['invoice_unique_id' => '']);
            
            //$invoice=$this->compareDigestValues($invoice);
           // echo $invoice;
            if (!empty($response['submissionUid']) && !empty($response['acceptedDocuments'][0]['uuid'])) {
                // ... existing success handling code ...
                // Record failure in message_header
                //echo $longId = $client->getDocument($response['acceptedDocuments'][0]['uuid']);
        
                // Extract the Long ID from the response
                //$longId = $longId['longID'] ?? null;

                DB::table('invoice')
                ->where('unique_id', $session) // match using unique_id
                ->update([
                'uuid' =>  $response['acceptedDocuments'][0]['uuid'] ?? null,
                'submission_uuid' => $response['submissionUid'] ?? null
                
            ]);

            DB::table('message_header')->insert([
                'document_id' => $record->invoice_no?? null,
                'type_submission' => 'INVOICE',
                'id_invoice' => $record->id_invoice,
                'hashing_256'=>hash('sha256', $invoice),
                'supplier_tin' => $supplier['tin_no'] ?? null,
                'customer_tin' => $customer['tin_no'] ?? null,
                'status_submission' => 'SUBMITTED',
                'submission_uuid' => $response['submissionUid'] ?? null,
                'uuid' => $response['acceptedDocuments'][0]['uuid'] ?? null,
                'error_message' => '',
                'submission_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'document_json' => json_encode($invoice , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'request_json' => json_encode($documents ?? []),
                'response_json' => json_encode($response ?? [])
            ]);
    
            } else if (!empty($response['errors'])) {
                throw new \Exception("Document submission failed: " . json_encode($response['errors']));
            }
    
            return response()->json($response);
    
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Document submission failed: ' . $e->getMessage());
            
           echo $e->getMessage();
   
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        
    }

    public function compareDigestValues($json) {

        $data = json_decode($json, true);

        if (!$data) {
            throw new Exception("Invalid JSON");
        }
    
        $digestResults = [];
    
        // Helper: generate digest (SHA256 + base64)
        $generateDigest = function($content) {
            return base64_encode(hash('sha256', $content, true));
        };
    
        // 1. id-doc-signed-data → document without UBLExtensions
        $unsignedData = $data;
        unset($unsignedData['Invoice'][0]['UBLExtensions']);
        $unsignedString = json_encode($unsignedData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $docDigest = $generateDigest($unsignedString);
    
        $refList = &$data['Invoice'][0]['UBLExtensions'][0]['UBLExtension'][0]['ExtensionContent'][0]
            ['UBLDocumentSignatures'][0]['SignatureInformation'][0]['Signature'][0]['SignedInfo'][0]['Reference'];
    
        foreach ($refList as &$ref) {
            if (isset($ref['Id']) && $ref['Id'] == 'id-doc-signed-data') {
                $original = $ref['DigestValue'][0]['_'];
                if ($original !== $docDigest) {
                    $ref['DigestValue'][0]['_'] = $docDigest;
                    $digestResults['id-doc-signed-data'] = ['old' => $original, 'new' => $docDigest];
                }
            }
            if (isset($ref['URI']) && $ref['URI'] == '#id-xades-signed-props') {
                $propsContent = $data['Invoice'][0]['UBLExtensions'][0]['UBLExtension'][0]['ExtensionContent'][0]
                    ['UBLDocumentSignatures'][0]['SignatureInformation'][0]['Signature'][0]['Object'][0]
                    ['QualifyingProperties'][0]['SignedProperties'][0];
                $propsString = json_encode($propsContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $propsDigest = $generateDigest($propsString);
                $original = $ref['DigestValue'][0]['_'];
                if ($original !== $propsDigest) {
                    $ref['DigestValue'][0]['_'] = $propsDigest;
                    $digestResults['id-xades-signed-props'] = ['old' => $original, 'new' => $propsDigest];
                }
            }
        }
    
        // 3. CertDigest (SigningCertificate > Cert > CertDigest)
        $certDigestRef = &$data['Invoice'][0]['UBLExtensions'][0]['UBLExtension'][0]['ExtensionContent'][0]
            ['UBLDocumentSignatures'][0]['SignatureInformation'][0]['Signature'][0]['Object'][0]
            ['QualifyingProperties'][0]['SignedProperties'][0]['SignedSignatureProperties'][0]
            ['SigningCertificate'][0]['Cert'][0]['CertDigest'][0]['DigestValue'][0]['_'];
    
        $certB64 = $data['Invoice'][0]['UBLExtensions'][0]['UBLExtension'][0]['ExtensionContent'][0]
            ['UBLDocumentSignatures'][0]['SignatureInformation'][0]['Signature'][0]['KeyInfo'][0]
            ['X509Data'][0]['X509Certificate'][0]['_'];
    
        $certBytes = base64_decode($certB64);
        $certDigestCalc = $generateDigest($certBytes);
    
        if ($certDigestRef !== $certDigestCalc) {
            $digestResults['CertDigest'] = ['old' => $certDigestRef, 'new' => $certDigestCalc];
            $certDigestRef = $certDigestCalc;
        }
    
      // print_r($digestResults);
    
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    

    }
    
    public function qr()
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        $id = 'QW2J0X82CBTDNVZFBYJ724WJ10';
        $longId = '6FH36EGF2R20F487BYJ724WJ10';
        echo $url = $client->generateDocumentQrCodeUrl($id, $longId);

    }


    


    public function cancelDocument(string $id, string $reason = 'Customer refund')
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->cancelDocument($id, $reason);
    }

    public function rejectDocument(string $id, string $reason = 'Customer reject')
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
   

        return $this->client->rejectDocument($id, $reason);
    }

    public function getRecentDocuments(
        int $pageNo = 1,
        int $pageSize = 20,
        ?string $submissionDateFrom = null,
        ?string $submissionDateTo = null,
        ?string $issueDateFrom = null,
        ?string $issueDateTo = null,
        string $direction = 'Sent',
        string $status = 'Valid',
        ?string $documentType = '01',
        ?string $receiverId = null,
        ?string $receiverIdType = null,
        ?string $receiverTin = null,
        ?string $issuerId = null,
        ?string $issuerIdType = null,
        ?string $issuerTin = null
    ) {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getRecentDocuments(
            $pageNo, $pageSize,
            $submissionDateFrom, $submissionDateTo,
            $issueDateFrom, $issueDateTo,
            $direction, $status, $documentType,
            $receiverId, $receiverIdType, $receiverTin,
            $issuerId, $issuerIdType, $issuerTin
        );
    }

    public function getSubmission(string $id, int $pageNo = 1, int $pageSize = 100)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getSubmission($id, $pageNo, $pageSize);
    }

    public function getDocument(string $id)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getDocument($id);
    }

    public function getDocumentDetail(string $id)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getDocumentDetail($id);
    }

    public function searchDocuments(
        ?string $id = null,
        ?\DateTime $submissionDateFrom = null,
        ?string $submissionDateTo = null,
        int $pageNo = 1,
        int $pageSize = 100,
        ?string $issueDateFrom = null,
        ?string $issueDateTo = null,
        string $direction = 'Sent',
        string $status = 'Valid',
        ?string $documentType = '01',
        ?string $searchQuery = null
    ) {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->searchDocuments(
            $id,
            $submissionDateFrom,
            $submissionDateTo,
            $pageNo,
            $pageSize,
            $issueDateFrom,
            $issueDateTo,
            $direction,
            $status,
            $documentType,
            $searchQuery
        );
    }

    public function generateDocumentQrCodeUrl(string $id, string $longId): string
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->generateDocumentQrCodeUrl($id, $longId);
    }
}
?>
