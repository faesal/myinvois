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
use App\Services\MyInvois\MyInvoisService;
use Exception;

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

    public function test($id)
    {
        $xml = app(MyInvoisService::class)->generate($id);

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function syncFromNlbh(Request $request)
    {
        $sale_id = $request->query('sale_id');

        if (!is_numeric($sale_id)) {
            return response()->json(['error' => 'Invalid sale_id.'], 400);
        }

        $pos = env('CUSTOM_INTEGRATE', 'nlbh');
        if (strtolower($pos) !== 'nlbh') {
            $pos = 'nlbh';
        }

        Session::put('connection_integrate', $pos);

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

        if (empty($config['host']) || empty($config['database']) || empty($config['username'])) {
            return response()->json(['error' => 'NLBH DB connection is not configured properly in .env'], 500);
        }

        Config::set("database.connections.dynamic_pos", $config);

        $existing = DB::table('invoice')
            ->where('sale_id_integrate', $sale_id)
            ->where('connection_integrate', $pos)
            ->first();

        if ($existing) {
            return redirect()->to(url("/createcustomer/{$existing->unique_id}"));
        }

        $id_supplier = DB::table('customer')
            ->where('connection_integrate', $pos)
            ->value('id_customer');

        if (!$id_supplier) {
            return response()->json(['error' => 'Supplier not found for POS (connection_integrate = nlbh)'], 404);
        }

        try {
            $order = DB::connection('dynamic_pos')
                ->table('orders')
                ->where('id', $sale_id)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

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

            $sst = (float)($order->sst ?? 0);
            $vat = (float)($order->vat ?? 0);
            $taxAmount = $sst + $vat;

            $grandTotal = (float)($order->grand_total ?? 0);
            $baseTotal  = (float)($order->total ?? 0);

            $price = $grandTotal > 0 ? $grandTotal : max($baseTotal + $taxAmount, 0);
            $taxableAmount = max($price - $taxAmount, 0);

            DB::beginTransaction();

            $unique_id = strtoupper(bin2hex(random_bytes(8)));

            $invoice_id = DB::table('invoice')->insertGetId([
                'invoice_no'                => $sale_id,
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
                'price'                     => $price,
                'taxable_amount'            => $taxableAmount,
                'tax_amount'                => $taxAmount,
                'tax_percent'               => 0,
                'payment_method'            => $order->payment_method ?? 'Cash',
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);

            $line = 0;
            foreach ($items as $it) {
                $line++;
                $qty = (int)($it->qty ?? 1);
                $lineTotal = (float)($it->total ?? 0);
                $unitPrice = $qty > 0 ? $lineTotal / $qty : $lineTotal;

                $desc = trim($it->product_name . (isset($it->size) && $it->size !== '' ? " ({$it->size})" : ''));

                DB::table('invoice_item')->insert([
                    'id_invoice'                 => $invoice_id,
                    'sale_id_integrate'          => $sale_id,
                    'connection_integrate'       => $pos,
                    'unique_id'                  => $unique_id,
                    'line_id'                    => $line,
                    'invoiced_quantity'          => $qty,
                    'line_extension_amount'      => $lineTotal,
                    'item_description'           => $desc,
                    'price_amount'               => $unitPrice,
                    'price_discount'             => 0,
                    'price_extension_amount'     => $lineTotal,
                    'item_clasification_value'   => '008',
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }

            DB::commit();

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

    public function create()
    {
        if (auth()->user()->role === 'admin') {
            $customers = DB::table('customer')
                ->whereNull('deleted')
                ->where('customer_type', 'CUSTOMER')
                ->orderBy('id_customer', 'desc')
                ->get();
        } else {
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

        $connection_integrate = 'kd';
        $id_supplier = 3;
        $uniqueId = Str::uuid();

        if ($request->buyer_type === 'new') {
            $customer_id = DB::table('customer')->insertGetId([
                'registration_name' => $request->company_name,
                'tin_no' => $request->tin_number,
                'connection_integrate' => $connection_integrate,
                'identification_no' => $request->registration_number,
                'email' => $request->email,
                'phone' => $request->phone,
                'city_name' => $request->city_name,
                'postal_zone' => $request->postal_zone,
                'identification_type' => $request->identification_type,
                'country_subentity_code' => $request->country_subentity_code,
                'country_code' => 'MYS',
                'address_line_1' => $request->address1,
                'address_line_2' => $request->address2,
                'address_line_3' => $request->address3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $customer_id = $request->customer_id;
        }

        $invoiceId = DB::table('invoice')->insertGetId([
            'invoice_no' => $request->invoice_no,
            'connection_integrate' => $connection_integrate,
            'id_customer' => $customer_id,
            'id_supplier' => $id_supplier,
            'invoice_type_code' => '01',
            'issue_date' => now(),
            'payment_note_term' => 'Cash',
            'created_at' => now(),
            'updated_at' => now(),
            'unique_id' => $uniqueId
        ]);

        $total = 0;
        $totalTax = 0;
        foreach ($request->items as $item) {
            $qty = floatval($item['qty']);
            $price = floatval($item['unit_price']);
            $taxRate = floatval($item['tax_rate']);
            $amount = $qty * $price;
            $tax = $amount * ($taxRate / 100);
            $totalItem = $amount + $tax;

            DB::table('invoice_item')->insert([
                'connection_integrate' => $connection_integrate,
                'unique_id' => $uniqueId,
                'id_customer' => $customer_id,
                'id_invoice' => $invoiceId,
                'item_description' => $item['description'],
                'invoiced_quantity' => $qty,
                'price_amount' => $price,
                'tax' => $taxRate,
                'price_amount' => $totalItem,
                'line_extension_amount' => $totalItem,
                'created_at' => now(),
                'updated_at' => now(),
                'item_clasification_value' => '022'
            ]);

            $totalTax += $taxRate;
            $total += $totalItem;
        }

        DB::table('invoice')->where('id_invoice', $invoiceId)->update([
            'price' => $total,
            'tax_amount' => $totalTax,
            'taxable_amount' => $total,
            'updated_at' => now()
        ]);

        session(['invoice_unique_id' => $uniqueId]);
        session(['id_supplier' => $id_supplier]);

        DB::commit();
        $invoice = new eInvoisModel;
        $invoice->submit($invoiceId);

        return redirect()->route('invoice.create')->with('success', 'Invoice created successfully!');
    }

public function qr_link($unique_id)
{
    // The eInvoisModel already expects the unique_id (UUID) to fetch the LHDN link
    $invoice = new eInvoisModel();
    echo $invoice->qr_link_lhdn($unique_id);
    exit();
}

    public function resubmit($id_invoice)
    {
        $record = DB::table('invoice')->where('id_invoice', $id_invoice)->first();
        $invoice = new eInvoisModel($record->connection_integrate);
        session(['invoice_type_code' => $record->invoice_type_code, 'invoice_unique_id' => $record->unique_id]);
        $result = $invoice->submit($id_invoice);
        print_r($result);
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

        if (auth()->user()->role != 'admin') {
            $query->where('connection_integrate', session('connection_integrate'));
        } elseif ($selectedConnection) {
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
    $developerId = auth()->user()->id; 
    $selectedIds = $request->input('selected_items', []);
    $selected_connection = session('connection_integrate');

    if (empty($selectedIds)) {
        return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
    }

    $items = DB::table('consolidate_invoice_item')->whereIn('id_invoice_item', $selectedIds)->get();

    if ($items->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No items found.'], 400);
    }

    $customer = DB::table('customer')
        ->where('connection_integrate', $selected_connection)
        ->where('customer_type', 'SUPPLIER')
        ->whereNull('deleted')
        ->first();

    $items = collect($items);
    $chunks = $items->chunk(25);
    $invoiceBaseNo = 'CONSOLIDATE-' . now()->format('Ymd-His');
    $version = 1;

    foreach ($chunks as $chunk) {
        // 🔥 Get sale_id_integrate from first item in chunk (Following reference)
        $saleId = $chunk->first()->sale_id_integrate;

        // Calculate total
        $total = (float) $chunk->sum('line_extension_amount');
        $uniqueId = (string) Str::uuid();
        $invoiceNo = $invoiceBaseNo . '-V' . $version;

        // -----------------------------------------------
        // STEP 1: INSERT INTO INVOICE (HEADER)
        // -----------------------------------------------
        $invoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $uniqueId,
            'sale_id_integrate' => $saleId,
            'connection_integrate' => $selected_connection,
            'invoice_status' => 'manual',
            'submission_status' => 'Pending', // Mark as Pending for Step 2
            'id_developer' => $developerId,
            'id_customer' => 6, 
            'id_supplier' => $customer->id_customer,
            'invoice_no' => $invoiceNo,
            'invoice_type_code' => '01',
            'issue_date' => now(),
            'tax_scheme_id' => 'OTH',
            'tax_category_id' => '01',
            'price' => number_format($total, 2, '.', ''),
            'taxable_amount' => number_format($total, 2, '.', ''),
            'tax_amount' => '0.00', // Reference logic: starts at 0.00
            'tax_percent' => '0.00',
            'payment_note_term' => 'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // -----------------------------------------------
        // STEP 2: INSERT INTO INVOICE_ITEM
        // -----------------------------------------------
        foreach ($chunk as $index => $item) {
            DB::table('invoice_item')->insert([
                'id_developer' => $developerId,
                'unique_id' => $uniqueId,
                'issue_date' => $item->issue_date,
                'connection_integrate' => $item->connection_integrate,
                'sale_id_integrate' => $item->sale_id_integrate,
                'id_consolidate_invoice' => $item->id_consolidate_invoice,
                'line_id' => $index + 1,
                'id_invoice' => $invoiceId,
                'invoiced_quantity' => $item->invoiced_quantity,
                'line_extension_amount' => number_format((float)$item->line_extension_amount, 2, '.', ''),
                'item_description' => $item->item_description,
                'price_amount' => number_format((float)$item->price_amount, 2, '.', ''),
                'price_discount' => number_format((float)($item->price_discount ?? 0), 2, '.', ''),
                'price_extension_amount' => number_format((float)$item->price_extension_amount, 2, '.', ''),
                'tax' => '0.00',
                'item_clasification_value' => '004', // Using reference default
                'created_at' => now(),
            ]);
        }

        // Mark original items as submitted
        DB::table('consolidate_invoice_item')
            ->whereIn('id_invoice_item', $chunk->pluck('id_invoice_item'))
            ->update([
                'submition_status' => 'submitted',
                'is_invoice' => 1,
                'updated_at' => now()
            ]);

        $version++;
    }

    return response()->json([
        'success' => true,
        'message' => 'Items consolidated successfully. Please submit them from the Listing page.'
    ]);

}  

public function show_invoice($unique_id)
{
    // 1. Fetch invoice by unique_id (UUID string)
    $invoice = DB::table('invoice')
        ->where('unique_id', $unique_id)
        ->first();

    if (!$invoice) {
        abort(404, 'Invoice not found.');
    }

    // 2. Fetch Supplier and Customer details using IDs from the invoice record
    // We use the IDs stored in the database instead of the URL parameters
    $supplier = DB::table('customer')->where('id_customer', $invoice->id_supplier)->first();
    
    // For customer, we use the stored ID, fallback to 6 (default) if empty
    $customer = DB::table('customer')->where('id_customer', $invoice->id_customer ?: 6)->first();
    
    // 3. Fetch items using the unique_id link
    $items = DB::table('invoice_item')->where('unique_id', $unique_id)->get();

    // 4. Fallback: If unique_id didn't yield items, try the primary key (id_invoice)
    if ($items->isEmpty()) {
        $items = DB::table('invoice_item')->where('id_invoice', $invoice->id_invoice)->get();
    }

    return view('invoices.show', compact('invoice', 'customer', 'supplier', 'items'));
}

    /**
     * Listing Submission with 'unique_id' and 'uuid' selection
     */
    public function listing_submission(Request $request)
    {
        $query = DB::table('invoice AS i')
            ->leftJoin('customer AS c', 'i.id_customer', '=', 'c.id_customer')
            ->select(
                'i.id_invoice',
                'i.unique_id',         // Required for the View Link
                'i.uuid',              // Required for the LHDN Cancel Link
                'i.invoice_no',
                'i.issue_date',
                'i.price',
                'i.submission_status',
                'i.invoice_status',
                'i.id_supplier',
                'i.id_customer',
                'c.registration_name as customer_name'
            );

        // --- Date Filtering ---
        if ($request->filled('start_date')) {
            $query->whereDate('i.issue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('i.issue_date', '<=', $request->end_date);
        }

        // --- Status Filtering ---
        if ($request->filled('status')) {
            if ($request->status == 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('i.submission_status')
                        ->orWhere('i.submission_status', '')
                        ->orWhere('i.submission_status', 'Pending');
                });
            } else {
                $query->where('i.submission_status', $request->status);
            }
        }

        // --- Role-based Filtering ---
        if (auth()->user()->role !== 'admin') {
            $query->where('i.connection_integrate', session('connection_integrate'));
        }

        $invoices = $query->orderBy('i.id_invoice', 'desc')->get();
        
        return view('invoices.submission', compact('invoices'));
    }
    public function syncFromPOS(Request $request)
    {
        $pos = $request->query('pos');
        $sale_id = $request->query('sale_id');

        if (!is_numeric($sale_id)) {
            return response()->json(['error' => 'Invalid sale_id.'], 400);
        }

        Session::put('connection_integrate', $pos);
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

        $existing = DB::table('invoice')
            ->where('sale_id_integrate', $sale_id)
            ->where('connection_integrate', $pos)
            ->first();

        $id_supplier = DB::table('customer')
            ->where('connection_integrate', $pos)
            ->value('id_customer');

        if (!$id_supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
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
            session(['invoice_unique_id' => $unique_id, 'id_supplier' => $id_supplier]);
            return Redirect::to(url("/createcustomer/{$unique_id}"));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to sync', 'details' => $e->getMessage()], 500);
        }
    }

    public function cancelDocument($uuid)
    {
        $invoice = new eInvoisModel;
        $reason = 'Customer refund';
        return $invoice->cancelDocument($uuid, $reason);
    }

    public function getSubmission(string $id, int $pageNo = 1, int $pageSize = 100)
    {
        $invoice = new eInvoisModel;
        $client = $invoice->getClient();
        $client->login();
        $client->setAccessToken($client->getAccessToken());
        return $client->getSubmission($id, $pageNo, $pageSize);
    }

    /**
     * Submit Selected to LHDN - Main Logic based on InvoiceSubmissionController
     */
public function submitSelectedLHDN(Request $request)
{
    // FIX: receive invoices from AJAX
    $selectedIds = $request->input('invoices', []);

    if (empty($selectedIds)) {
        return response()->json([
            'success' => false,
            'message' => 'No invoices selected.'
        ], 400);
    }

    // Validate invoices belong to developer/connection
    $invoices = DB::table('invoice')
        ->whereIn('id_invoice', $selectedIds)
        ->get();

    if ($invoices->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No valid invoices found.'
        ], 400);
    }

    $successCount = 0;
    $failCount = 0;
    $errors = [];

    foreach ($invoices as $inv) {
        try {
            // ---------------------------------------------------------
            // 1. REPAIR HEADER DATA (Force "0.00" VARCHAR format)
            // Fixes: "Missing TaxTotal taxAmount"
            // ---------------------------------------------------------
            $taxAmount = (float)($inv->tax_amount ?? 0);
            $taxableAmount = (float)($inv->taxable_amount ?? 0);
            $price = (float)($inv->price ?? 0);

            DB::table('invoice')->where('id_invoice', $inv->id_invoice)->update([
                'tax_amount'     => number_format($taxAmount, 2, '.', ''),
                'taxable_amount' => number_format($taxableAmount, 2, '.', ''),
                'price'          => number_format($price, 2, '.', ''),
                'updated_at'     => now()
            ]);

            // ---------------------------------------------------------
            // 2. REPAIR ITEM DATA (Required for LHDN TaxTotal validation)
            // ---------------------------------------------------------
            DB::table('invoice_item')
                ->where('id_invoice', $inv->id_invoice)
                ->get()
                ->each(function($item) {
                    DB::table('invoice_item')
                        ->where('id_invoice_item', $item->id_invoice_item)
                        ->update([
                            'tax'                      => number_format((float)($item->tax ?? 0), 2, '.', ''),
                            'line_extension_amount'    => number_format((float)$item->line_extension_amount, 2, '.', ''),
                            'price_amount'             => number_format((float)$item->price_amount, 2, '.', ''),
                            'item_clasification_value' => $item->item_clasification_value ?? '004'
                        ]);
                });

            // ---------------------------------------------------------
            // 3. SESSION SETUP (Based on InvoiceSubmissionController)
            // ---------------------------------------------------------
            // Set the dynamic connection for API keys
            Session::put('connection_integrate', $inv->connection_integrate);

            // Reset status first to prevent bleed from previous loop iteration
            session(['consolidate_status' => '']);

            // REFERENCE LOGIC: Set to '1' if customer is empty or default consolidate buyer (ID 6)
            if (empty($inv->id_customer) || $inv->id_customer == 6) {
                session(['consolidate_status' => '1']);
            }

            session([
                'invoice_type_code' => '01',
                'invoice_unique_id' => $inv->unique_id
            ]);

            // ---------------------------------------------------------
            // 4. SUBMIT TO LHDN
            // ---------------------------------------------------------
            $model = new \App\Models\eInvoisModel;
            $model->submit($inv->id_invoice);

            // Update status on success
            DB::table('invoice')->where('id_invoice', $inv->id_invoice)->update([
                'submission_status' => 'Submitted',
                'updated_at'        => now()
            ]);

            $successCount++;
        } catch (\Exception $e) {
            $failCount++;
            $errors[] = "Inv #{$inv->invoice_no}: " . $e->getMessage();

            DB::table('invoice')->where('id_invoice', $inv->id_invoice)->update([
                'submission_status' => 'Failed',
                'updated_at'        => now()
            ]);
        }
    }

    return response()->json([
        'success' => ($failCount === 0),
        'message' => "Processed " . count($selectedIds) . " invoices. Success: $successCount, Failed: $failCount",
        'errors'  => $errors,
        'connection_integrate' => session('connection_integrate')
    ], 200);
}
public function deleteInvoice($id)
{
    $invoice = DB::table('invoice')
        ->where('id_invoice', $id)
        ->first();

    // Safety Check: Only allow delete if not already successfully submitted to LHDN
    if (!$invoice || strtolower($invoice->submission_status) === 'submitted') {
        return redirect()->back()->with('error', 'Cannot delete a successfully submitted invoice.');
    }

    DB::beginTransaction();
    try {
        // 1. If it was a consolidated invoice, reset the original items
        // so they show up again in the "Consolidate" list
        DB::table('consolidate_invoice_item')
            ->where('submition_status', 'submitted')
            ->whereExists(function ($query) use ($id) {
                $query->select(DB::raw(1) )
                      ->from('invoice_item')
                      ->whereRaw('invoice_item.id_consolidate_invoice = consolidate_invoice_item.id_consolidate_invoice')
                      ->where('invoice_item.id_invoice', $id);
            })
            ->update([
                'submition_status' => null,
                'is_invoice' => null,
                'updated_at' => now()
            ]);

        // 2. Delete the items and the header
        DB::table('invoice_item')->where('id_invoice', $id)->delete();
        DB::table('invoice')->where('id_invoice', $id)->delete();

        DB::commit();
        return redirect()->back()->with('success', 'Invoice removed. Items are now available for re-consolidation.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
    }
}
}