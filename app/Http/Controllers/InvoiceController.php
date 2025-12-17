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
use App\Models\eInvoisModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class InvoiceController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $prodMode;

    public function __construct()
    {
       /* $this->clientId = "68459bb8-ed45-4ea6-8846-5ba2740a5e2f";
        $this->clientSecret = "ed9d15f7-1886-48f7-b642-9d85ab995881";
        $this->prodMode = true;*/
    }

    /**
     * Sync ONE order from the custom NLBH POS into invoice/invoice_item.
     * GET /invoice/sync-nlbh?sale_id=123
     */
        public function syncFromNlbh(Request $request)
        {
            // Accept "sale_id" (maps to orders.id)
            $sale_id = $request->query('sale_id');

            if (!is_numeric($sale_id)) {
                return response()->json(['error' => 'Invalid sale_id.'], 400);
            }

            // Force the integration to NLBH only
            $pos = env('CUSTOM_INTEGRATE', 'nlbh');
            if (strtolower($pos) !== 'nlbh') {
                // Even if env is different, we strictly allow only nlbh here
                $pos = 'nlbh';
            }

            // Put the connection flag into session for downstream logic
            Session::put('connection_integrate', $pos);

            // Build dynamic DB connection from .env (DB_NLBH_*)
            $config = [
                'driver'    => env('DB_NLBH_CONNECTION', 'mysql'),
                'host'      => env('DB_NLBH_HOST'),
                'port'      => env('DB_NLBH_PORT', 3306),
                'database'  => env('DB_NLBH_DATABASE'),
                'username'  => env('DB_NLBH_USERNAME'),
                'password'  => env('DB_NLBH_PASSWORD'),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ];

            // Basic sanity check
            if (empty($config['host']) || empty($config['database']) || empty($config['username'])) {
                return response()->json(['error' => 'NLBH DB connection is not configured properly in .env'], 500);
            }

            Config::set("database.connections.dynamic_pos", $config);

            // Check if invoice already exists for this sale_id & integration
            $existing = DB::table('invoice')
                ->where('sale_id_integrate', $sale_id)
                ->where('connection_integrate', $pos)
                ->first();

            if ($existing) {
                // Already synced — go to the same customer creation step
                return redirect()->to(url("/createcustomer/{$existing->unique_id}"));
            }

            // Get supplier (your own "customer" record tagged with this integration)
            $id_supplier = DB::table('customer')
                ->where('connection_integrate', $pos)
                ->value('id_customer');

            if (!$id_supplier) {
                return response()->json(['error' => 'Supplier not found for POS (connection_integrate = nlbh)'], 404);
            }

            try {
                // ---- Pull order header ----
                $order = DB::connection('dynamic_pos')
                    ->table('orders')
                    ->where('id', $sale_id)
                    ->first();

                if (!$order) {
                    return response()->json(['error' => 'Order not found'], 404);
                }

                // ---- Pull order items + product names ----
                $items = DB::connection('dynamic_pos')
                    ->table('order_items as oi')
                    ->leftJoin('products as p', 'oi.product_id', '=', 'p.id')
                    ->where('oi.order_id', $sale_id)
                    ->select(
                        'oi.id',
                        'oi.product_id',
                        'oi.size',
                        'oi.addons',
                        'oi.qty',
                        'oi.total',
                        DB::raw("COALESCE(p.slug, 'Unnamed Item') as product_name")
                    )
                    ->get();

                // Compute amounts safely
                $sst = (float)($order->sst ?? 0);
                $vat = (float)($order->vat ?? 0);
                $taxAmount = $sst + $vat;

                $grandTotal = (float)($order->grand_total ?? 0);
                $baseTotal  = (float)($order->total ?? 0);

                // Prefer grand_total if present; otherwise fall back to total + taxes
                $price = $grandTotal > 0 ? $grandTotal : max($baseTotal + $taxAmount, 0);
                $taxableAmount = max($price - $taxAmount, 0);

                DB::beginTransaction();

                $unique_id = strtoupper(bin2hex(random_bytes(8)));

                // ---- Insert invoice ----
                $invoice_id = DB::table('invoice')->insertGetId([
                    'invoice_no'                => $sale_id, // using order id as invoice_no
                    'unique_id'                 => $unique_id,
                    'sale_id_integrate'         => $sale_id,
                    'connection_integrate'      => $pos,
                    'id_supplier'               => $id_supplier,

                    'invoice_status'            => 'Valid',
                    'invoice_type_code'         => '01',
                    'tax_category_id'           => '01',
                    'tax_exemption_reason'      => '',
                    'tax_scheme_id'             => 'OTH',

                    'payment_note_term'         => 'CASH',
                    'payment_financial_account' => '-',

                    'issue_date'                => $order->created_at ?? now(),

                    'price'                     => $price,           // gross
                    'taxable_amount'            => $taxableAmount,   // net (approx.)
                    'tax_amount'                => $taxAmount,
                    'tax_percent'               => 0,                // set as needed
                    'payment_method'            => $order->payment_method ?? 'Cash',

                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);

                // ---- Insert invoice items ----
                $line = 0;
                foreach ($items as $it) {
                    $line++;
                    $qty = (int)($it->qty ?? 1);
                    $lineTotal = (float)($it->total ?? 0);
                    $unitPrice = $qty > 0 ? $lineTotal / $qty : $lineTotal;

                    // Build an item description (product + optional size)
                    $desc = trim($it->product_name . (isset($it->size) && $it->size !== '' ? " ({$it->size})" : ''));

                    DB::table('invoice_item')->insert([
                        'id_invoice'                 => $invoice_id,
                        'sale_id_integrate'          => $sale_id,
                        'connection_integrate'       => $pos,
                        'unique_id'                  => $unique_id,

                        'line_id'                    => $line,
                        'invoiced_quantity'          => $qty,
                        'line_extension_amount'      => $lineTotal,     // total for the line
                        'item_description'           => $desc,

                        // Unit price & extensions
                        'price_amount'               => $unitPrice,
                        'price_discount'             => 0,              // no discount info in schema
                        'price_extension_amount'     => $lineTotal,

                        // Default classification (adjust if you have mapping)
                        'item_clasification_value'   => '008',

                        'created_at'                 => now(),
                        'updated_at'                 => now(),
                    ]);
                }

                DB::commit();

                // Stash for downstream pages
                Session::put('invoice_unique_id', $unique_id);
                Session::put('id_supplier', $id_supplier);

                return redirect()->to(url("/createcustomer/{$unique_id}"));
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'error'   => 'Failed to sync invoice (nlbh)',
                    'details' => $e->getMessage(),
                ], 500);
            }
        }
    


    public function validateTaxPayerTin($tin, $idType, $idValue)
    {
        $invoice = new eInvoisModel;
        $response = $invoice->validate_tin($tin, $idType, $idValue);

        print_r($response);
    }

    public function create()
    {
        if (auth()->user()->role === 'admin') {
            // Admin boleh tengok semua
            $customers = DB::table('customer')
                ->whereNull('deleted')
                ->where('customer_type', 'CUSTOMER')
                ->orderBy('id_customer', 'desc')
                ->get();
        } else {
            // Subscriber hanya boleh tengok customer dengan connection_integrate mereka
            $customers = DB::table('customer')
                ->whereNull('deleted')
                ->where('customer_type', 'CUSTOMER')
                ->where('connection_integrate', session('connection_integrate'))
                ->orderBy('id_customer', 'desc')
                ->get();
        }
        return view('invoices.create', compact('customers'));
    }

    public function store_create(Request $request)
    {
        DB::beginTransaction();

        $connection_integrate='kd';
        $id_supplier=3;
        $uniqueId = Str::uuid();
            // 1. Handle Customer
            if ($request->buyer_type === 'new') {
                $customer_id = DB::table('customer')->insertGetId([
                    'registration_name' => $request->company_name,
                    'tin_no' => $request->tin_number,
                    'connection_integrate'=>$connection_integrate,
                    'identification_no' => $request->registration_number,
                    'email' => $request->email,
                    'phone'=>$request->phone,
                    'city_name'=>$request->city_name,
                    'postal_zone'=>$request->postal_zone,
                    'identification_type'=>$request->identification_type,
                    'country_subentity_code'=>$request->country_subentity_code,
                    'country_code'=>'MYS',
                    'address_line_1' => $request->address1,
                    'address_line_2' => $request->address2,
                    'address_line_3' => $request->address3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $customer_id = $request->customer_id;
            }
      
            // 2. Create Invoice
            $invoiceId = DB::table('invoice')->insertGetId([
                'invoice_no' => $request->invoice_no,
                'connection_integrate'=>$connection_integrate,
                'id_customer' => $customer_id,
                'id_supplier' => $id_supplier,
                'invoice_type_code' => '01',
                'issue_date' => now(),
                'payment_note_term' => 'Cash',
                'created_at' => now(),
                'updated_at' => now(),
                'unique_id'=>$uniqueId
            ]);

   
            // 3. Create Items
            $total = 0;
            $totalTax=0;
            foreach ($request->items as $item) {
                $qty = floatval($item['qty']);
                $price = floatval($item['unit_price']);
                $taxRate = floatval($item['tax_rate']);
                $amount = $qty * $price;
                $tax = $amount * ($taxRate / 100);
                $totalItem = $amount + $tax;

                DB::table('invoice_item')->insert([
                    'connection_integrate'=>$connection_integrate,
                    'unique_id'=>$uniqueId,
                    'id_customer'=>$customer_id,
                    'id_invoice' => $invoiceId,
                    'item_description' => $item['description'],
                    'invoiced_quantity' => $qty,
                    'price_amount' => $price,
                    'tax' => $taxRate,
                    'price_amount' => $totalItem,
                    'line_extension_amount' => $totalItem,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'item_clasification_value'=>'022'
                ]);

                $totalTax +=$taxRate;
                $total += $totalItem;
            }

           

            // 4. Update invoice total
            DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                'price' => $total,
                'tax_amount' => $totalTax,
                'taxable_amount'=>$total,
                'updated_at' => now()
            ]);
            

            session(['invoice_unique_id' => $uniqueId]);
            session(['id_supplier' => $id_supplier]);
           

            
            DB::commit();
            $invoice = new eInvoisModel;
            $invoice->submit($invoiceId);
            
            return redirect()->route('invoice.create')->with('success', 'Invoice created successfully!');
       
    }
    

    public function qr_link($uuid){

        $invoice = new eInvoisModel;
        echo $response = $invoice->qr_link($uuid);
        exit();
    }


    public function resubmit($id_invoice){

        $record = DB::table('invoice')->where('id_invoice',$id_invoice)->first();
       // print_r($record);
        $invoice = new eInvoisModel;
        $invoice_type_code=session(['invoice_type_code' => '01','invoice_unique_id'=>$record->unique_id]);
        $invoice->submit($id_invoice);
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

        // Jika bukan admin, tapis ikut session connection_integrate
        if (auth()->user()->role != 'admin') {
            $query->where('connection_integrate', session('connection_integrate'));
        }
        // Jika admin dan ada selectedConnection, tapis berdasarkan pilihan
        elseif ($selectedConnection) {
            $query->where('connection_integrate', $selectedConnection);
        }

        $query->whereNull('submition_status');

        $items = $query->orderBy('issue_date')->get();
 

        $availableConnectionsQuery = DB::table('consolidate_invoice_item')
        ->select('connection_integrate')
        ->distinct();
    
        if (auth()->user()->role !== 'admin') {
            $availableConnectionsQuery->where('connection_integrate', session('connection_integrate'));
        }
        
        $availableConnections = $availableConnectionsQuery->pluck('connection_integrate')->toArray();
        

        return view('consolidate.select', compact('items', 'start', 'end', 'availableConnections'));
    }

public function submitSelected(Request $request)
{
    $selectedIds = $request->input('selected_items', []);

    $selected_connection = session('connection_integrate');
    
    if (empty($selectedIds)) {
        return response()->json([
            'success' => false,
            'message' => 'No items selected.'
        ], 400);
    }

    $items = DB::table('consolidate_invoice_item')
        ->whereIn('id_invoice_item', $selectedIds)
        ->get();

    if ($items->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No invoice items found for submission.'
        ], 400);
    }

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

        $total = $chunk->sum('line_extension_amount');
        $uniqueId = Str::uuid();
        $invoiceNo = $invoiceBaseNo . '-V' . $version;

        $invoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $uniqueId,
            'connection_integrate' => $selected_connection,
            'invoice_status' => 'manual',
            'id_customer' => 6,
            'id_supplier' => $customer->id_customer,
            'invoice_no' => $invoiceNo,
            'invoice_type_code' => '01',
            'issue_date' => now(),
            'price' => $total,
            'taxable_amount' => 0,
            'payment_note_term' => 'CASH',
            'tax_amount' => 0,
            'tax_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($chunk as $index => $item) {
            DB::table('invoice_item')->insert([
                'unique_id' => $uniqueId,
                'issue_date' => $item->issue_date,
                'connection_integrate' => $item->connection_integrate,
                'sale_id_integrate' => $item->sale_id_integrate,
                'id_consolidate_invoice' => $item->id_consolidate_invoice,
                'line_id' => $index + 1,
                'id_invoice' => $invoiceId,
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
            ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
            ->update([
                'submition_status' => 'submitted',
                'updated_at' => now()
            ]);

        session([
            'invoice_unique_id' => $uniqueId,
            'consolidate_status' => 1,
            'invoice_id' => $invoiceNo
        ]);

        $invoice = new eInvoisModel;
        $invoice->submit($invoiceId);

        $version++;
    }

    return response()->json([
        'success' => true,
        'message' => 'Selected items submitted as multiple invoices.'
    ]);
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

    public function listing_submission(Request $request)
{
    $query = DB::table('invoice')
        ->leftJoin('customer', 'invoice.id_customer', '=', 'customer.id_customer')
        ->select(
            'invoice.uuid',
            'invoice.submission_status',
            'invoice.invoice_no',
            'invoice.id_supplier',
            'invoice.id_customer',
            'invoice.id_invoice',
            'invoice.issue_date',
            'invoice.price',
            'invoice.invoice_status',
            'customer.registration_name as customer_name'
        );

    // ⬇️ Filter ikut tarikh mula
    if ($request->filled('start_date')) {
        $query->whereDate('invoice.issue_date', '>=', $request->start_date);
    }

    // ⬇️ Filter ikut tarikh akhir
    if ($request->filled('end_date')) {
        $query->whereDate('invoice.issue_date', '<=', $request->end_date);
    }

    // ⬇️ Filter ikut status submission
    if ($request->filled('status')) {
        $query->where('invoice.submission_status', $request->status);
    }

    // ⬇️ Tambah role-based filtering
    if (auth()->user()->role !== 'admin') {
        $query->where('invoice.connection_integrate', session('connection_integrate'));
    }

    // ⬇️ Dapatkan keputusan
    $invoices = $query->orderBy('invoice.id_invoice', 'asc')->get();

    return view('invoices.submission', compact('invoices'));
}


    public function syncFromPOS(Request $request)
    {
        $pos = $request->query('pos'); // e.g., bill
        $sale_id = $request->query('sale_id');
    
        if (!is_numeric($sale_id)) {
            return response()->json(['error' => 'Invalid sale_id.'], 400);
        }
    
        Session::put('connection_integrate', $pos);
    
        // Dynamically load config from .env
        $connections = explode(',', env('INTEGRATE_POS_CONNECTIONS'));
        if (!in_array($pos, $connections)) {
            return response()->json(['error' => 'POS connection not allowed'], 403);
        }
    
        $connectionKey = strtoupper($pos);
        $config = [
            'driver' => 'mysql',
            'host' => env("DB_{$connectionKey}_HOST"),
            'database' => env("DB_{$connectionKey}_DATABASE"),
            'username' => env("DB_{$connectionKey}_USERNAME"),
            'password' => env("DB_{$connectionKey}_PASSWORD"),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ];
    
        Config::set("database.connections.dynamic_pos", $config);
    
        // Check if invoice already exists
        $existing = DB::table('invoice')
            ->where('sale_id_integrate', $sale_id)
            ->where('connection_integrate', $pos)
            ->first();
    

        $id_supplier = DB::table('customer')
            ->where('connection_integrate', $pos)
            ->value('id_customer');

        if (!$id_supplier) {
            return response()->json(['error' => 'Supplier not found for POS'], 404);
        }

        session(['id_supplier' => $id_supplier]);

        
    
        try {
           
    
            $sale = DB::connection('dynamic_pos')->table('phppos_sales')->where('sale_id', $sale_id)->first();
            if (!$sale) return response()->json(['error' => 'Sale not found'], 404);
    
            $items = DB::connection('dynamic_pos')
                ->table('phppos_sales_items as si')
                ->join('phppos_items as i', 'si.item_id', '=', 'i.item_id')
                ->where('si.sale_id', $sale_id)
                ->select('si.*', 'i.name as item_name')
                ->get();
    
            DB::beginTransaction();
            $unique_id = strtoupper(bin2hex(random_bytes(8)));
      
            $invoice_id = DB::table('invoice')->insertGetId([
                'invoice_no' => $sale_id,
                'unique_id' => $unique_id,
                'sale_id_integrate' => $sale_id,
                'connection_integrate' => $pos,
                'id_supplier' => $id_supplier,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            foreach ($items as $item) {
                DB::table('invoice_item')->insert([
                    'id_invoice' => $invoice_id,
                    'sale_id_integrate' => $sale_id,
                    'connection_integrate' => $pos,
                    'unique_id' => $unique_id,
                    'line_id' => $item->line,
                    'invoiced_quantity' => $item->quantity_purchased,
                    'line_extension_amount' => $item->total,
                    'item_description' => $item->item_name ?? 'Unnamed Item',
                    'price_amount' => $item->subtotal,
                    'price_discount' => $item->discount_percent,
                    'price_extension_amount' => $item->subtotal,
                    'item_clasification_value' => '008',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
    
            DB::commit();
           
            session(['invoice_unique_id' => $unique_id]);
            session(['id_supplier' => $id_supplier]);
    
            return Redirect::to(url("/createcustomer/{$unique_id}"));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to sync invoice', 'details' => $e->getMessage()], 500);
        }

        if ($existing) {
            return Redirect::to(url("/createcustomer/{$existing->unique_id}"));
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
        'invoice_type_code' => '11',
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

    $invoice = new eInvoisModel;
    $invoice->submit($id);


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
        
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        $id = 'QW2J0X82CBTDNVZFBYJ724WJ10';
        $longId = '6FH36EGF2R20F487BYJ724WJ10';
        echo $url = $client->generateDocumentQrCodeUrl($id, $longId);

    }

    public function cancelDocument($uuid)
    {
     
        $invoice = new eInvoisModel;
        $reason = 'Customer refund';
        return $invoice->cancelDocument($uuid, $reason);
    }

    public function rejectDocument(string $id, string $reason = 'Customer reject')
    {
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
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
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
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
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getSubmission($id, $pageNo, $pageSize);
    }

    public function getDocument(string $id)
    {
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getDocument($id);
    }

    public function getDocumentDetail(string $id)
    {
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
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
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
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
        $invoice = new eInvoisModel;
       

        $client = $invoice->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->generateDocumentQrCodeUrl($id, $longId);
    }
}
?>
