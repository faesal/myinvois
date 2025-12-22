<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntegrationInvoiceController2 extends Controller
{
    /**
     * Store invoice from MySyncTax integration (with tax + customer output)
     */

public function invoice(Request $request)
{
    $payload = json_decode($request->getContent(), true);

    if (!is_array($payload)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid JSON received'
        ], 400);
    }

    // =====================================================
    // 1. AUTHENTICATION
    // =====================================================
    $apiKey    = data_get($payload, 'mysynctax_key');
    $apiSecret = data_get($payload, 'mysynctax_secret');

    if (!$apiKey || !$apiSecret) {
        return response()->json([
            'status' => 'unauthorized',
            'message' => 'mysynctax_key and mysynctax_secret are required'
        ], 401);
    }

    $client = DB::table('connection_integrate')
        ->where('mysynctax_key', $apiKey)
        ->where('mysynctax_secret', $apiSecret)
        ->first();

    $supplier = DB::table('customer')
    ->where('connection_integrate',  $client->code)
    ->first();
   
    if (!$client) {
        return response()->json([
            'status' => 'unauthorized',
            'message' => 'Invalid MySyncTax credentials'
        ], 401);
    }

    $connCode = $client->code;

// =====================================================
// CUSTOMER (CREATE OR REUSE – FULL FIELDS)
// =====================================================
$customerPayload = data_get($payload, 'customer');

if (!$customerPayload || !data_get($customerPayload, 'tin_no')) {
    return response()->json([
        'status'  => 'error',
        'message' => 'customer.tin_no is required'
    ], 422);
}

$customer = DB::table('customer')
    ->where('connection_integrate', $connCode)
    ->where('tin_no', data_get($customerPayload, 'tin_no'))
    ->whereNull('deleted')
    ->first();

$customerStatus = 'existing';

if (!$customer) {
    $customerId = DB::table('customer')->insertGetId([
        'id_developer'            => $client->id_developer,
        'connection_integrate'    => $connCode,
        'customer_type'           => 'CUSTOMER',
        'tin_no'                  => data_get($customerPayload, 'tin_no'),
        'unique_id'               => strtoupper(Str::random(12)),

        'registration_name'       => data_get($customerPayload, 'registration_name'),
        'identification_no'       => data_get($customerPayload, 'identification_no'),
        'identification_type'     => data_get($customerPayload, 'identification_type'),
        'sst_registration'        => data_get($customerPayload, 'sst_registration'),

        'phone'                   => data_get($customerPayload, 'phone'),
        'email'                   => data_get($customerPayload, 'email'),

        'city_name'               => data_get($customerPayload, 'city_name'),
        'postal_zone'             => data_get($customerPayload, 'postal_zone'),
        'country_subentity_code'  => data_get($customerPayload, 'country_subentity_code'),
        'country_code'            => data_get($customerPayload, 'country_code', 'MYS'),

        'address_line_1'          => data_get($customerPayload, 'address_line_1'),
        'address_line_2'          => data_get($customerPayload, 'address_line_2'),
        'address_line_3'          => data_get($customerPayload, 'address_line_3'),

        'created_at'              => now(),
        'updated_at'              => now(),
    ]);

    $customer = DB::table('customer')
        ->where('id_customer', $customerId)
        ->first();

    $customerStatus = 'created';
}


    // =====================================================
    // 3. EXTRACT INVOICE DATA (UNCHANGED)
    // =====================================================
    $uniqueId  = sha1($request->getContent());
    $invoiceNo = data_get($payload, 'invoice_no');
    $issueDate = now();
    $saleId    = (int) data_get($payload, 'sale_id_integrate', 0);
    $items     = data_get($payload, 'items', []);

    if (!$invoiceNo || empty($items)) {
        return response()->json([
            'status' => 'error',
            'message' => 'invoice_no and items are required'
        ], 400);
    }

    $consolidateTotalItem = collect($items ?? [])->sum(function ($i) {
        return (int) data_get($i, 'invoiced_quantity', 0);
    });

    $amountBefore = (float) data_get($payload, 'total_amount', 0);
    $listSaleItemId = array_map('intval', data_get($payload, 'list_sale_item_id', []));

    // =====================================================
    // 4. DUPLICATE CHECK (UNCHANGED)
    // =====================================================
    $existing = DB::table('invoice')
        ->where('connection_integrate', $connCode)
        ->where('unique_id', $uniqueId)
        ->first();

    if ($existing) {
        return response()->json([
            'status' => 'duplicate_ignored',
            'mysynctax_uuid' => $existing->unique_id
        ], 409);
    }

    $listItemId = collect($items)->pluck('item_id')->toArray();

    // =====================================================
    // 5. TRANSACTION (HEADER + ITEMS)
    // =====================================================
    $idCon = DB::transaction(function () use (
        $payload, $uniqueId, $invoiceNo, $issueDate,
        $saleId, $consolidateTotalItem, $amountBefore,
        $listSaleItemId, $items, $connCode, $client, $request, $customer,$supplier
    ) {
        $invoice_id = DB::table('invoice')->insertGetId([
            'invoice_no'               => $invoiceNo,
            'unique_id'                => $uniqueId,
        
            'sale_id_integrate'        => $saleId,
            'connection_integrate'     => $connCode,
        
            'id_developer'             => $client->id_developer,
            'id_customer'              =>  $customer->id_customer ,
            'id_supplier'              => $supplier->id_customer,
        
            'invoice_status'           => 'Valid',
            'invoice_type_code'        => '01', // Normal Invoice
            'tax_category_id'          => '01',
            'tax_exemption_reason'     => '',
            'tax_scheme_id'            => 'OTH',
        
            'payment_note_term'        => data_get($payload, 'payment_note_term', 'CASH'),
            'payment_financial_account'=> '-',
            'payment_method'           => data_get($payload, 'payment_method', 'Cash'),
        
            'issue_date'               => $issueDate,
        
            'price'                    => $amountBefore,
            'taxable_amount'           => data_get($payload, 'taxable_amount', 0),
            'tax_amount'               => data_get($payload, 'tax_amount', 0),
            'tax_percent'              => data_get($payload, 'tax_percent', 0),
        
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
        
        $rows = [];

        foreach ($items as $index => $it) {

            $qty   = (float) data_get($it, 'invoiced_quantity', 0);
            $price = (float) data_get($it, 'unit_price', 0);

            $rows[] = [
                'id_invoice'             => $invoice_id,
                'sale_id_integrate'      => $saleId,
                'connection_integrate'   => $connCode,
                'unique_id'              => $uniqueId,
                'id_developer'            => $client->id_developer,
                'id_customer'            => $customer->id_customer,
                'line_id'                => data_get($it, 'sorting_id', $index + 1),
                'invoiced_quantity'      => $qty,

                'line_extension_amount'  => data_get($it, 'total', $qty * $price),

                'item_description'       => data_get($it, 'item_description', 'Unnamed Item'),
                'price_amount'           => $price,
                'item_id_integrate'      => data_get($it, 'item_id', 0),
                'price_discount'         => data_get($it, 'price_discount', 0),
                'price_extension_amount' => $qty * $price,
                'tax'  =>  data_get($it, 'tax', 0),
                'item_clasification_value'=>'022',

                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }


        DB::table('invoice_item')->insert($rows);
       
        
        return $invoice_id;

        
    });

    // =====================================================
    // 6. FINAL UPDATE + QR (UNCHANGED)
    // =====================================================


    $model = new \App\Models\eInvoisModel;
    
    $invoice_type_code=session(['invoice_type_code' => '01','invoice_unique_id'=>$uniqueId]);
    $model->submit($idCon);
    // =====================================================
    // 7. RESPONSE
    // =====================================================
    return response()->json([
        'status'           => 'ok',
        'mysynctax_uuid'   => $uniqueId,
        'invoice_id'=>$idCon,
       // 'qr_url'           => "{$appUrl}/redirect/{$shortCode}",
        'customer_status'  => $customerStatus,
        'customer_id'      => $customer->id_customer
    ], 201);
}

public function note(Request $request)
{
    DB::beginTransaction();

    try {

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON received'
            ], 400);
        }

        /* =====================================================
           1. AUTHENTICATION
        ===================================================== */
        $apiKey    = data_get($payload, 'mysynctax_key');
        $apiSecret = data_get($payload, 'mysynctax_secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'mysynctax_key and mysynctax_secret are required'
            ], 401);
        }

        $client = DB::table('connection_integrate')
            ->where('mysynctax_key', $apiKey)
            ->where('mysynctax_secret', $apiSecret)
            ->first();

        if (!$client) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid MySyncTax credentials'
            ], 401);
        }

        $connCode = $client->code;

        $supplier = DB::table('customer')
            ->where('connection_integrate', $connCode)
            ->first();

        /* =====================================================
           1A. SUPPLIER BASIC SANITY CHECK
        ===================================================== */
        if ($supplier) {
            if (!empty($supplier->email) &&
                !filter_var($supplier->email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Supplier email format invalid');
            }

            if (!empty($supplier->phone) &&
                !preg_match('/^[0-9]{7,15}$/', $supplier->phone)) {
                throw new \Exception('Supplier phone format invalid');
            }
        }

        /* =====================================================
           2. NOTE TYPE
        ===================================================== */
        $noteType = data_get($payload, 'note_type');

        if (!in_array($noteType, ['credit', 'debit', 'refund'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid note_type'
            ], 400);
        }

        if ($noteType === 'credit') {
            $invoiceTypeCode = '02';
            $sign = -1;
            $label = 'Credit';
        } elseif ($noteType === 'debit') {
            $invoiceTypeCode = '03';
            $sign = 1;
            $label = 'Debit';
        } else {
            $invoiceTypeCode = '04';
            $sign = -1;
            $label = 'Refund';
        }

        /* =====================================================
           3. ORIGINAL INVOICE
        ===================================================== */
        $originalInvoiceId = data_get($payload, 'sale_id_integrate');

        if (!is_numeric($originalInvoiceId)) {
            throw new \Exception('original_invoice_id must be numeric');
        }
        $mysynctax_uuid = data_get($payload, 'mysynctax_uuid');
        $original = DB::table('invoice')
            ->where('sale_id_integrate', $originalInvoiceId)
            ->where('unique_id', $mysynctax_uuid)
            ->first();

        if (!$original) {
            return response()->json([
                'status' => 'error',
                'message' => 'Original invoice not found'
            ], 404);
        }

        $uniqueId = (string) Str::uuid();

        /* =====================================================
           4. CREATE NOTE INVOICE
        ===================================================== */
        $noteInvoiceId = DB::table('invoice')->insertGetId([
            'unique_id' => $uniqueId,
            'connection_integrate' => $connCode,
            'sale_id_integrate' =>$originalInvoiceId,
            'id_developer' => $client->id_developer,
            'invoice_no' => strtoupper($label) . '-NOTE-' . now()->format('YmdHis'),
            'invoice_type_code' => $invoiceTypeCode,
            'issue_date' => now(),
            'id_customer' => $original->id_customer,
            'id_supplier' => $original->id_supplier,
            'previous_id_invoice' => $original->id_invoice,
            'previous_invoice_no' => $original->invoice_no,
            'previous_uuid' => $original->uuid,
            'payment_note_term' => 'CASH',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        /* =====================================================
           5. ITEMS + STRICT VALIDATION
        ===================================================== */
        $items = data_get($payload, 'items', []);

        if (!is_array($items) || count($items) === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Items are required'
            ], 400);
        }

        foreach ($items as $item) {

            $itemId = data_get($item, 'item_id');

        

            $oriItem = DB::table('invoice_item')
                ->where('item_id_integrate', $itemId)
                ->first();

            if (!$oriItem) {
                throw new \Exception('Invalid invoice item reference');
            }

            $qty      = (float) data_get($item, 'qty', 0);
            $price    = (float) data_get($item, 'price', 0);
            $discount = (float) data_get($item, 'discount', 0);
            $tax      = (float) data_get($item, 'tax', 0);
            $desc     = data_get($item, 'description', '');

            /* ==== NUMERIC SANITY ==== */
            if ($qty <= 0 || $qty > 100000) {
                throw new \Exception('Invalid qty value');
            }

            if ($price < 0 || $price > 1000000) {
                throw new \Exception('Invalid price value');
            }

         

            if ($tax < 0 || $tax > 100000) {
                throw new \Exception('Invalid tax value');
            }

            if (strlen($desc) > 500) {
                throw new \Exception('Item description too long');
            }

            $lineAmount = (($qty * $price) - $discount) * $sign;

            DB::table('invoice_item')->insert([
                'id_invoice' => $noteInvoiceId,
                'id_developer' => $client->id_developer,
                'unique_id' => $uniqueId,
                'connection_integrate' => $connCode,
                'previous_id_invoice' => $original->id_invoice,
                'previous_id_invoice_item' => $oriItem->id_invoice_item,
                'previous_amount' => $oriItem->line_extension_amount,
                'line_id' => $oriItem->line_id,
                'invoiced_quantity' => $qty,
                'price_amount' => $price,
                'price_discount' => $discount,
                'line_extension_amount' => $lineAmount,
                'price_extension_amount' => $lineAmount,
                'tax' => $tax * $sign,
                'item_description' => $desc,
                'item_clasification_value' => '022',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /* =====================================================
           6. TOTAL RECALC
        ===================================================== */
        $total = DB::table('invoice_item')
            ->where('id_invoice', $noteInvoiceId)
            ->sum('line_extension_amount');

        $taxTotal = DB::table('invoice_item')
            ->where('id_invoice', $noteInvoiceId)
            ->sum('tax');

        DB::table('invoice')->where('id_invoice', $noteInvoiceId)->update([
            'price' => $total,
            'taxable_amount' => $total,
            'tax_amount' => $taxTotal,
            'updated_at' => now()
        ]);

        /* =====================================================
           7. SUBMIT TO MYINVOIS
        ===================================================== */
        session([
            'invoice_unique_id' => $uniqueId,
            'previous_uuid' => $original->uuid,
            'previous_invoice_no' => $original->invoice_no,
            'invoice_type_code' => $invoiceTypeCode
        ]);

        $model = new \App\Models\eInvoisModel;
        $model->submit($noteInvoiceId);


        session()->forget([
            'invoice_unique_id',
            'previous_uuid',
            'previous_invoice_no',
            'invoice_type_code'
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'note_type' => $noteType,
            'invoice_id' => $noteInvoiceId,
            'message' => "{$label} Note submitted successfully"
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

}
