<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\eInvoisModel;
use Illuminate\Support\Facades\Session;
class IntegrationInvoiceController extends Controller
{
    /**
     * Store invoice data from external API clients
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

     public function validateTIN(Request $request)
     {
         $model = new \App\Models\eInvoisModel;
         $request->validate([
             'mysynctax_key'    => 'required|string',
             'mysynctax_secret' => 'required|string'
         ]);
     
         // =====================================================
         // 2. Authenticate MySyncTax credentials
         // =====================================================
         $client = DB::table('connection_integrate')
             ->where('mysynctax_key', $request->mysynctax_key)
             ->where('mysynctax_secret', $request->mysynctax_secret)
             ->first();
         //echo 'test';
         Session::put('connection_integrate', $client->code);
         $response=$model->validateTaxPayerTin($request->tin,$request->idType='NRIC', $request->idValue='930105045119');
         print_r($response);
     }

    public function addSupplier(Request $request)
    {
        // =====================================================
        // 1. Validate base request
        // =====================================================
        $request->validate([
            'mysynctax_key'    => 'required|string',
            'mysynctax_secret' => 'required|string',
            'supplier'        => 'required|array|min:1',
        ]);
    
        // =====================================================
        // 2. Authenticate MySyncTax credentials
        // =====================================================
        $client = DB::table('connection_integrate')
            ->where('mysynctax_key', $request->mysynctax_key)
            ->where('mysynctax_secret', $request->mysynctax_secret)
            ->first();
    
        if (!$client) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid MySyncTax credentials'
            ], 401);
        }
    
        $connCode = $client->code;
        $results  = [];
    
        DB::beginTransaction();
    
        try {
    
            foreach ($request->supplier as $index => $cust) {
    
                // =================================================
                // 3. Validate EACH customer
                // =================================================
                $validator = Validator::make($cust, [
                    'tin_no'                  => 'required|string',
                    'registration_name'       => 'required|string',
                    'identification_no'       => 'required|string',
                    'identification_type'     => 'required|string',
                    'phone'                   => 'required|string',
                    'email'                   => 'required|email',
                    'city_name'               => 'required|string',
                    'postal_zone'             => 'required|string',
                    'state_code'              => 'required|string',
                    'country_code'            => 'required|string',
                    'address_line_1'          => 'required|string',
                    'address_line_2'          => 'required|string',
                    'address_line_3'          => 'required|string',
                ]);
    
                if ($validator->fails()) {
                    $results[] = [
                        'tin_no' => $cust['tin_no'] ?? null,
                        'status' => 'validation_failed',
                        'errors' => $validator->errors()
                    ];
                    continue;
                }
    
                // =================================================
                // 4. Check existing customer (by TIN + connection)
                // =================================================
                $existing = DB::table('customer')
                    ->where('connection_integrate', $connCode)
                    ->where('tin_no', $cust['tin_no'])
                    ->whereNull('deleted')
                    ->first();
    
                // =================================================
                // 5A. UPDATE if exists
                // =================================================
                if ($existing) {
    
                    DB::table('customer')
                        ->where('id_customer', $existing->id_customer)
                        ->update([
                            'registration_name'      => $cust['registration_name'],
                            'identification_no'      => $cust['identification_no'],
                            'identification_type'    => $cust['identification_type'],
                            'sst_registration'       => $cust['sst_registration'] ?? $existing->sst_registration,
                            'is_selfbill_supplier'   => 1,
                            'phone'                  => $cust['phone'],
                            'email'                  => $cust['email'],
    
                            'city_name'              => $cust['city_name'],
                            'postal_zone'            => $cust['postal_zone'],
                            'country_subentity_code' => $cust['state_code'],
                            'country_code'           => $cust['country_code'],
    
                            'address_line_1'         => $cust['address_line_1'],
                            'address_line_2'         => $cust['address_line_2'],
                            'address_line_3'         => $cust['address_line_3'],
    
                            'updated_at'             => now(),
                        ]);
    
                    $results[] = [
                        'tin_no'      => $cust['tin_no'],
                        'status'      => 'updated',
                        'id_customer' => $existing->id_customer
                    ];
    
                    continue;
                }
    
                // =================================================
                // 5B. INSERT if not exists
                // =================================================
                $idCustomer = DB::table('customer')->insertGetId([
                    'id_developer'           => $client->id_developer,
                    'connection_integrate'   => $connCode,
                    'customer_type'          => 'CUSTOMER',
                    'tin_no'                 => $cust['tin_no'],
                    'unique_id'              => strtoupper(Str::random(15)),
    
                    'registration_name'      => $cust['registration_name'],
                    'identification_no'      => $cust['identification_no'],
                    'identification_type'    => $cust['identification_type'],
                    'sst_registration'       => $cust['sst_registration'] ?? null,
    
                    'phone'                  => $cust['phone'],
                    'email'                  => $cust['email'],
    
                    'city_name'              => $cust['city_name'],
                    'postal_zone'            => $cust['postal_zone'],
                    'country_subentity_code' => $cust['state_code'],
                    'country_code'           => $cust['country_code'],
    
                    'address_line_1'         => $cust['address_line_1'],
                    'address_line_2'         => $cust['address_line_2'],
                    'address_line_3'         => $cust['address_line_3'],
                    'is_selfbill_supplier'   => 1,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
    
                $results[] = [
                    'tin_no'      => $cust['tin_no'],
                    'status'      => 'created',
                    'id_customer' => $idCustomer
                ];
            }
    
            DB::commit();
    
            return response()->json([
                'status'  => 'ok',
                'message' => 'Supplier processed successfully',
                'results' => $results
            ], 200);
    
        } catch (\Throwable $e) {
    
            DB::rollBack();
    
            Log::error('Add Supplier API failed', [
                'error' => $e->getMessage()
            ]);
    
            return response()->json([
                'status'  => false,
                'message' => 'Failed to add supplier'
            ], 500);
        }
    }

    public function addCustomer(Request $request)
    {
        // =====================================================
        // 1. Validate base request
        // =====================================================
        $request->validate([
            'mysynctax_key'    => 'required|string',
            'mysynctax_secret' => 'required|string',
            'customers'        => 'required|array|min:1',
        ]);
    
        // =====================================================
        // 2. Authenticate MySyncTax credentials
        // =====================================================
        $client = DB::table('connection_integrate')
            ->where('mysynctax_key', $request->mysynctax_key)
            ->where('mysynctax_secret', $request->mysynctax_secret)
            ->first();
    
        if (!$client) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid MySyncTax credentials'
            ], 401);
        }
    
        $connCode = $client->code;
        $results  = [];
    
        DB::beginTransaction();
    
        try {
    
            foreach ($request->customers as $index => $cust) {
    
                // =================================================
                // 3. Validate EACH customer
                // =================================================
                $validator = Validator::make($cust, [
                    'tin_no'                  => 'required|string',
                    'registration_name'       => 'required|string',
                    'identification_no'       => 'required|string',
                    'identification_type'     => 'required|string',
                    'phone'                   => 'required|string',
                    'email'                   => 'required|email',
                    'city_name'               => 'required|string',
                    'postal_zone'             => 'required|string',
                    'state_code'              => 'required|string',
                    'country_code'            => 'required|string',
                    'address_line_1'          => 'required|string',
                    'address_line_2'          => 'required|string',
                    'address_line_3'          => 'required|string',
                ]);
    
                if ($validator->fails()) {
                    $results[] = [
                     
                        'tin_no' => $cust['tin_no'] ?? null,
                        'status' => 'validation_failed',
                        'errors' => $validator->errors()
                    ];
                    continue;
                }
    
                // =================================================
                // 4. Check existing customer (by TIN + connection)
                // =================================================
                $existing = DB::table('customer')
                    ->where('connection_integrate', $connCode)
                    ->where('tin_no', $cust['tin_no'])
                    ->whereNull('deleted')
                    ->first();
    
                // =================================================
                // 5A. UPDATE if exists
                // =================================================
                if ($existing) {
    
                    DB::table('customer')
                        ->where('id_customer', $existing->id_customer)
                        ->update([
                            'registration_name'      => $cust['registration_name'],
                            'identification_no'      => $cust['identification_no'],
                            'identification_type'    => $cust['identification_type'],
                            'sst_registration'       => $cust['sst_registration'] ?? $existing->sst_registration,
    
                            'phone'                  => $cust['phone'],
                            'email'                  => $cust['email'],
    
                            'city_name'              => $cust['city_name'],
                            'postal_zone'            => $cust['postal_zone'],
                            'country_subentity_code' => $cust['state_code'],
                            'country_code'           => $cust['country_code'],
    
                            'address_line_1'         => $cust['address_line_1'],
                            'address_line_2'         => $cust['address_line_2'],
                            'address_line_3'         => $cust['address_line_3'],
    
                            'updated_at'             => now(),
                        ]);
    
                    $results[] = [
                        'tin_no'      => $cust['tin_no'],
                        'status'      => 'updated',
                        'id_customer' => $existing->id_customer
                    ];
    
                    continue;
                }
    
                // =================================================
                // 5B. INSERT if not exists
                // =================================================
                $idCustomer = DB::table('customer')->insertGetId([
                    'id_developer'           => $client->id_developer,
                    'connection_integrate'   => $connCode,
                    'customer_type'          => 'CUSTOMER',
                    'tin_no'                 => $cust['tin_no'],
                    'unique_id'              => strtoupper(Str::random(15)),
    
                    'registration_name'      => $cust['registration_name'],
                    'identification_no'      => $cust['identification_no'],
                    'identification_type'    => $cust['identification_type'],
                    'sst_registration'       => $cust['sst_registration'] ?? null,
    
                    'phone'                  => $cust['phone'],
                    'email'                  => $cust['email'],
    
                    'city_name'              => $cust['city_name'],
                    'postal_zone'            => $cust['postal_zone'],
                    'country_subentity_code' => $cust['state_code'],
                    'country_code'           => $cust['country_code'],
    
                    'address_line_1'         => $cust['address_line_1'],
                    'address_line_2'         => $cust['address_line_2'],
                    'address_line_3'         => $cust['address_line_3'],
    
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
    
                $results[] = [
                    'tin_no'      => $cust['tin_no'],
                    'status'      => 'created',
                    'id_customer' => $idCustomer
                ];
            }
    
            DB::commit();
    
            return response()->json([
                'status'  => 'ok',
                'message' => 'Customers processed successfully',
                'results' => $results
            ], 200);
    
        } catch (\Throwable $e) {
    
            DB::rollBack();
    
            Log::error('Add customer API failed', [
                'error' => $e->getMessage()
            ]);
    
            return response()->json([
                'status'  => false,
                'message' => 'Failed to add customers'
            ], 500);
        }
    }
    


    public function validate(Request $request)
    {
        // 1. Validate JSON input
        $request->validate([
            'mysynctax_key'     => 'required|string',
            'mysynctax_secret'  => 'required|string',
            'identification_type' => 'required|string',
            'identification_no'   => 'required|string',
        ]);
    
        // 2. Find customer by MySyncTax credential
        $customer = DB::table('connection_integrate')
            ->where('mysynctax_key', $request->mysynctax_key)
            ->where('mysynctax_secret', $request->mysynctax_secret)
            ->first();
    
        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid MySyncTax credentials'
            ], 401);
        }
    
        // 3. Set connection_integrate (important for multi ERP / POS)
        Session::put('connection_integrate', $customer->code);
    
        // 4. Call Model (LHDN API)
        $model = new eInvoisModel();
    
        $response = $model->searchTaxPayerTin(
            $request->taxpayer_name ?? '',
            $request->identification_type,
            $request->identification_no,
            $request->file_type ?? null
        );
    
        return response()->json([
            'status' => true,
            'connection_integrate' => $customer->connection_integrate,
            'data' => $response
        ]);
    }
    

    public function store(Request $request)
    {
        // Validate incoming JSON structure
        $validator = Validator::make($request->all(), [
            'invoice_no' => 'nullable|string|max:200',
            'invoice_type_code' => 'nullable|string|max:50',
            'issue_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'payment_note_term' => 'nullable|string|max:255',
            'tax_percent' => 'nullable|numeric',
            'include_signature' => 'nullable|boolean',
            
            // Customer information (optional)
            'customer' => 'nullable|array',
            'customer.tin_no' => 'nullable|string|max:50',
            'customer.registration_name' => 'nullable|string|max:255',
            'customer.identification_no' => 'nullable|string|max:50',
            'customer.identification_type' => 'nullable|string|max:50',
            'customer.email' => 'nullable|email|max:100',
            'customer.phone' => 'nullable|string|max:50',
            'customer.address_line_1' => 'nullable|string|max:255',
            'customer.city_name' => 'nullable|string|max:100',
            'customer.postal_zone' => 'nullable|string|max:20',
            'customer.country_subentity_code' => 'nullable|string|max:10',
            
            // Invoice items (required)
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string',
            'items.*.invoiced_quantity' => 'required|numeric|min:0',
            'items.*.price_amount' => 'required|numeric|min:0',
            'items.*.price_discount' => 'nullable|numeric|min:0',
            'items.*.item_clasification_value' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $connectionCode = $request->input('authenticated_connection');
            $uniqueId = (string) Str::uuid();
            $issueDate = Carbon::parse($request->issue_date);
            
            // Handle customer if provided
            $customerId = null;
            if ($request->has('customer') && !empty($request->customer)) {
                $customerId = $this->handleCustomer($request->customer, $connectionCode);
            }

            // Calculate totals from items
            $items = $request->items;
            $totalBefore = 0;
            $totalDiscount = 0;
            $totalAfter = 0;

            foreach ($items as $item) {
                $quantity = $item['invoiced_quantity'];
                $priceAmount = $item['price_amount'];
                $discount = $item['price_discount'] ?? 0;
                
                $extensionAmount = $quantity * $priceAmount;
                $lineAmount = $extensionAmount - $discount;
                
                $totalBefore += $extensionAmount;
                $totalDiscount += $discount;
                $totalAfter += $lineAmount;
            }

            // Apply tax if specified
            $taxPercent = $request->tax_percent ?? 0;
            $taxAmount = 0;
            if ($taxPercent > 0) {
                $taxAmount = ($totalAfter * $taxPercent) / 100;
                $totalAfter += $taxAmount;
            }

            // Insert consolidate_invoice
            $invoiceId = DB::table('consolidate_invoice')->insertGetId([
                'unique_id' => $uniqueId,
                'connection_integrate' => $connectionCode,
                'id_customer' => $customerId,
                'invoice_status' => 'pending',
                'invoice_no' => $request->invoice_no,
                'invoice_type_code' => $request->invoice_type_code ?? '01',
                'issue_date' => $issueDate,
                'consolidate_date' => $issueDate->format('Y-m-d'),
                'consolidate_total_item' => count($items),
                'consolidate_complete_total' => count($items),
                'consolidate_complete_status' => 'completed',
                'consolidate_total_amount_before' => $totalBefore,
                'consolidate_total_amount_after' => $totalAfter,
                'price' => $totalAfter,
                'taxable_amount' => $totalBefore,
                'tax_amount' => $taxAmount,
                'tax_percent' => $taxPercent,
                'payment_method' => $request->payment_method,
                'payment_note_term' => $request->payment_note_term ?? 'Cash',
                'include_signature' => $request->include_signature ?? false,
                'json_receive' => json_encode($request->all()),
                'created_at' => now(),
                'updated_at' => now(),
                
            ]);

            // Insert consolidate_invoice_item entries
            foreach ($items as $index => $item) {
                $quantity = $item['invoiced_quantity'];
                $priceAmount = $item['price_amount'];
                $discount = $item['price_discount'] ?? 0;
                $extensionAmount = $quantity * $priceAmount;
                $lineAmount = $extensionAmount - $discount;

                DB::table('consolidate_invoice_item')->insert([
                    'unique_id' => $uniqueId,
                    'connection_integrate' => $connectionCode,
                    'id_customer' => $customerId,
                    'id_consolidate_invoice' => $invoiceId,
                    'issue_date' => $issueDate->format('Y-m-d'),
                    'line_id' => $index + 1,
                    'invoiced_quantity' => $quantity,
                    'item_description' => $item['item_description'],
                    'price_amount' => $priceAmount,
                    'price_discount' => $discount,
                    'price_extension_amount' => $extensionAmount,
                    'line_extension_amount' => $lineAmount,
                    'item_clasification_value' => $item['item_clasification_value'] ?? '004',
                    'submition_status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            Log::info('Invoice created via API', [
                'connection' => $connectionCode,
                'invoice_id' => $invoiceId,
                'unique_id' => $uniqueId,
                'total_items' => count($items)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => [
                    'id_consolidate_invoice' => $invoiceId,
                    'unique_id' => $uniqueId,
                    'invoice_no' => $request->invoice_no,
                    'total_items' => count($items),
                    'total_amount' => $totalAfter,
                    'issue_date' => $issueDate->toIso8601String()
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('API Invoice Creation Failed', [
                'connection' => $request->input('authenticated_connection'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle customer data - create or link existing
     *
     * @param array $customerData
     * @param string $connectionCode
     * @return int|null
     */
    private function handleCustomer(array $customerData, string $connectionCode)
    {
        if (empty($customerData['tin_no'])) {
            return null;
        }

        // Check if customer already exists
        $existingCustomer = DB::table('customer')
            ->where('tin_no', $customerData['tin_no'])
            ->where('connection_integrate', $connectionCode)
            ->whereNull('deleted')
            ->first();

        if ($existingCustomer) {
            return $existingCustomer->id_customer;
        }

        // Create new customer
        $customerId = DB::table('customer')->insertGetId([
            'connection_integrate' => $connectionCode,
            'customer_type' => 'CUSTOMER',
            'tin_no' => $customerData['tin_no'],
            'unique_id' => strtoupper(substr(md5(mt_rand()), 0, 15)),
            'registration_name' => $customerData['registration_name'] ?? null,
            'identification_no' => $customerData['identification_no'] ?? null,
            'identification_type' => $customerData['identification_type'] ?? null,
            'email' => $customerData['email'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'address_line_1' => $customerData['address_line_1'] ?? null,
            'address_line_2' => $customerData['address_line_2'] ?? null,
            'address_line_3' => $customerData['address_line_3'] ?? null,
            'city_name' => $customerData['city_name'] ?? null,
            'postal_zone' => $customerData['postal_zone'] ?? null,
            'country_subentity_code' => $customerData['country_subentity_code'] ?? null,
            'country_code' => 'MYS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $customerId;
    }

    public function storeFromIntegration(Request $request)
    {
        // =========================================================
        // 1. DECODE JSON
        // =========================================================
        $payload = json_decode($request->getContent(), true);
    
        if (!is_array($payload)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid JSON received'
            ], 400);
        }
    
        // =========================================================
        // 2. AUTH
        // =========================================================
        $apiKey    = data_get($payload, 'mysynctax_key');
        $apiSecret = data_get($payload, 'mysynctax_secret');
    
        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'status'  => 'unauthorized',
                'message' => 'mysynctax_key and mysynctax_secret are required'
            ], 401);
        }
    
        $client = DB::table('connection_integrate')
            ->where('mysynctax_key', $apiKey)
            ->where('mysynctax_secret', $apiSecret)
            ->first();
    
        if (!$client) {
            return response()->json([
                'status'  => 'unauthorized',
                'message' => 'Invalid mysynctax_key or mysynctax_secret'
            ], 401);
        }
    
        $connCode = $client->code;
    
        // =========================================================
        // 3. BASIC DATA
        // =========================================================
        $invoiceNo = data_get($payload, 'invoice_no');
        $saleId    = (int) data_get($payload, 'sale_id_integrate');
        $items     = data_get($payload, 'items', []);
    
        if (!$invoiceNo || !$saleId || empty($items)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'invoice_no, sale_id_integrate & items are required'
            ], 400);
        }
    
        // ✅ STABLE UNIQUE ID
        $uniqueId  = sha1($connCode . '|' . $invoiceNo . '|' . $saleId);
        $issueDate = now();
    
        // =========================================================
        // 4. PREVENT DUPLICATE
        // =========================================================
        $existing = DB::table('consolidate_invoice')
            ->where('connection_integrate', $connCode)
            ->where('unique_id', $uniqueId)
            ->first();
    
        if ($existing) {
            return response()->json([
                'status'         => 'duplicate_ignored',
                'mysynctax_uuid' => $existing->unique_id,
            ], 409);
        }
    
        // =========================================================
        // 5. SERVER-SIDE CALCULATION (WAJIB)
        // =========================================================
        $taxableAmount = 0;
        $taxAmount     = 0;
        $totalQty      = 0;
        $rows          = [];
        $listItemId    = [];
        $priceBefore   =0;
        foreach ($items as $index => $it) {
    
            $qty        = (float) data_get($it, 'invoiced_quantity', 0);
            $unitPrice = (float) data_get($it, 'unit_price', 0);
            $discount  = (float) data_get($it, 'price_discount', 0);
            $itemTax   = (float) data_get($it, 'tax_amount', 0);
    
            $lineExtension  = $qty * $unitPrice;                 // BEFORE DISCOUNT
            $priceBefore    += $lineExtension;
            $priceAfter     = $lineExtension - $discount;         // TAXABLE
    
            $taxableAmount += $priceAfter;
            $taxAmount     += $itemTax;
            $totalQty      += $qty;
    
            $listItemId[] = data_get($it, 'item_id');
    
            $rows[] = [
                'unique_id'                => $uniqueId,
                'connection_integrate'     => $connCode,
                'sale_id_integrate'        => $saleId,
                'id_developer'             => $client->id_developer,
    
                'item_id_integrate'        => data_get($it, 'item_id'),
                'issue_date'               => $issueDate,
                'line_id'                  => data_get($it, 'sorting_id', $index + 1),
                'invoiced_quantity'        => $qty,
    
                // ✅ CORRECT FINANCIAL LOGIC
                'line_extension_amount'    => $lineExtension,
                'price_discount'           => $discount,
                'price_extension_amount'   => $priceAfter,
                'tax'                      => $itemTax,
    
                'item_description'         => data_get($it, 'item_description'),
                'price_amount'             => $unitPrice,
    
                'item_clasification_type'  => '',
                'item_clasification_value' => '004',
    
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }
    
        $taxPercent = $taxableAmount > 0
            ? round(($taxAmount / $taxableAmount) * 100, 2)
            : 0;
    
        // =========================================================
        // 6. TRANSACTION INSERT
        // =========================================================
        $idCon = DB::transaction(function () use (
            $request,
            $uniqueId,
            $invoiceNo,
            $issueDate,
            $saleId,
            $connCode,
            $client,
            $rows,
            $taxableAmount,
            $taxAmount,
            $taxPercent,
            $totalQty,
            $priceBefore
        ) {
    
            $idCon = DB::table('consolidate_invoice')->insertGetId([
                'unique_id'                       => $uniqueId,
                'json_receive'                    => $request->getContent(),
                'consolidate_date'                => now(),
    
                'id_developer'                    => $client->id_developer,
                'connection_integrate'            => $connCode,
                'sale_id_integrate'               => $saleId,
                'invoice_no'                      => $invoiceNo,
                'issue_date'                      => $issueDate,
    
                // ✅ HEADER CALC
                'consolidate_total_item'          => $totalQty,
                'consolidate_total_amount_before' => $taxableAmount,
                'price'                           => $priceBefore,        
                'taxable_amount'                  => $taxableAmount,
                'tax_amount'                      => $taxAmount,
                'tax_percent'                     => $taxPercent,
                'tax_category_id'                 => '01',
                'tax_scheme_id'                   => 'OTH',
    
                'created_at'                      => now(),
                'updated_at'                      => now(),
            ]);
    
            foreach (array_chunk($rows, 500) as $chunk) {
                foreach ($chunk as &$r) {
                    $r['id_consolidate_invoice'] = $idCon;
                }
                DB::table('consolidate_invoice_item')->insert($chunk);
            }
    
            return $idCon;
        });
    
        // =========================================================
        // 7. FINAL VERIFY
        // =========================================================
        $amountAfter = DB::table('consolidate_invoice_item')
            ->where('unique_id', $uniqueId)
            ->sum('price_extension_amount');
    
        $completeStatus = bccomp($taxableAmount, $amountAfter, 2) === 0
            ? 'completed'
            : 'unmatched';
    
        DB::table('consolidate_invoice')
            ->where('id_invoice', $idCon)
            ->update([
                'consolidate_list_sale_item_id' => implode(',', $listItemId),
                'consolidate_total_amount_after'=> $amountAfter,
                'consolidate_complete_status'   => $completeStatus,
                'updated_at'                    => now(),
            ]);
    
        // =========================================================
        // 8. SHORT URL
        // =========================================================
        $appUrl    = rtrim(env('APP_URL'), '/');
        $longUrl   = "{$appUrl}/myinvois/{$uniqueId}";
        $shortCode = Str::random(7);
    
        DB::table('shorten_url')->insert([
            'short_code'   => $shortCode,
            'original_url' => $longUrl,
            'clicks'       => 0,
            'created_at'   => now()
        ]);
    
        return response()->json([
            'status'         => 'ok',
            'mysynctax_uuid' => $uniqueId,
            'qr_url'         => "{$appUrl}/redirect/{$shortCode}"
        ], 201);
    }
    

}