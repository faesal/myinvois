<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SelfBillController extends Controller
{
/**
     * Store invoice from MySyncTax integration (with tax + customer output)
     */
    public function invoice(Request $request, $mode = 'normal')
    {
        $payload = json_decode($request->getContent(), true);
        $customerPayload = data_get($payload, 'supplier');
    
        if (!is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON received'
            ], 400);
        }
    
        /*
        |--------------------------------------------------------------------------
        | TIN RULE BASED ON MODE
        |--------------------------------------------------------------------------
        */
        $blockedTIN = [
            'EI00000000010',
            'EI00000000020',
            'EI00000000030',
            'EI00000000040'
        ];
    
        $tin_no = data_get($customerPayload, 'tin_no');
        $item_clasification_code = '036';

        $identification_no = data_get($customerPayload, 'identification_no');
        $identification_type = data_get($customerPayload, 'identification_type');


        if ($mode === 'normal') {
            // normal invoice → TIN ini tak dibenarkan
            if (in_array($tin_no, $blockedTIN)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This TIN No. is not allowed for normal invoice'
                ], 422);
            }
        }
    
        if ($mode === 'general') {
            // invoice_generaltin → hanya TIN khas dibenarkan
            if (!in_array($tin_no, $blockedTIN)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This API only accepts General TIN (EI00000000010/20/30/40)'
                ], 422);
            }

            // SELFBILL
            if ($tin_no === 'EI00000000010') {
                $item_clasification_code = '004';
                if($identification_no == ''){
                    $identification_no = 'NA';
                }
    
            } elseif ($tin_no === 'EI00000000020') {
                $item_clasification_code = '022';
    
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This TIN No. not allowed for selfbill foreign supplier , please use EI00000000030'
                ], 422);
    
            } elseif ($tin_no === 'EI00000000030') {
                $item_clasification_code = '036';
                if($identification_type != 'BRN'){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'LHDN : EI00000000030 - Please use Identification type BRN !'
                    ], 400);
                }
        
                if($identification_no == ''){
                    $identification_no = 'NA';
                }
    
            } elseif ($tin_no === 'EI00000000040') {
                $item_clasification_code = '036';
    
                if($identification_type != 'BRN'){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'LHDN : EI00000000040 - Please use Identification type BRN !'
                    ], 400);
                }
    
                if($identification_no != 'NA'){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'LHDN : EI00000000040 - Only support Identification No NA !'
                    ], 400);
                }
            }
        }
  
        // =====================================================
        // 1. AUTHENTICATION (UNCHANGED)
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
    
        if (!$client) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid MySyncTax credentials'
            ], 401);
        }

        if(data_get($customerPayload, 'country_code') == ''){
            return response()->json([
                'status' => 'error',
                'message' => 'Country code is required'
            ], 400);
            exit();
        }

        if(data_get($customerPayload, 'state_code') == ''){
            return response()->json([
                'status' => 'error',
                'message' => 'State code is required'
            ], 400);
            exit();
        }

        if(data_get($customerPayload, 'phone') == ''){
            return response()->json([
                'status' => 'error',
                'message' => 'Phone No. is required'
            ], 400);
            exit();
        }

        if(data_get($customerPayload, 'email') == ''){
            return response()->json([
                'status' => 'error',
                'message' => 'Email is is required'
            ], 400);
            exit();
        }
    
        $connCode = $client->code;
    
        $supplier = DB::table('customer')
            ->where('connection_integrate', $connCode)
            ->first();
    
        // =====================================================
        // CUSTOMER (UNCHANGED LOGIC)
        // =====================================================
        if (!$customerPayload || !data_get($customerPayload, 'tin_no')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'customer.tin_no is required'
            ], 422);
        }
    
        $customer = DB::table('customer')
            ->where('connection_integrate', $connCode)
            ->where('registration_name', data_get($customerPayload, 'registration_name'))
            ->where('tin_no', data_get($customerPayload, 'tin_no'))
            ->where('identification_no', data_get($customerPayload, 'identification_no'))
            ->whereNull('deleted')
            ->first();
    
        $customerStatus = 'existing';
    
        if (!$customer) {
            $customerId = DB::table('customer')->insertGetId([
                'id_developer'           => $client->id_developer,
                'connection_integrate'   => $connCode,
                'customer_type'          => 'CUSTOMER',
                'tin_no'                 => $tin_no,
                'unique_id'              => strtoupper(Str::random(12)),
                'registration_name'      => data_get($customerPayload, 'registration_name'),
                'identification_no'      => $identification_no,
                'identification_type'    => $identification_type,
                'sst_registration'       => data_get($customerPayload, 'sst_registration'),
                'phone'                  => data_get($customerPayload, 'phone'),
                'email'                  => data_get($customerPayload, 'email'),
                'city_name'              => data_get($customerPayload, 'city_name'),
                'postal_zone'            => data_get($customerPayload, 'postal_zone'),
                'country_subentity_code' => data_get($customerPayload, 'state_code'),
                'country_code'           => data_get($customerPayload, 'country_code', 'MYS'),
                'address_line_1'         => data_get($customerPayload, 'address_line_1'),
                'address_line_2'         => data_get($customerPayload, 'address_line_2'),
                'address_line_3'         => data_get($customerPayload, 'address_line_3'),
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
    
            $customer = DB::table('customer')->where('id_customer', $customerId)->first();
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

        $existing = DB::table('invoice')
            ->where('connection_integrate', $connCode)
            ->where('sale_id_integrate',   $saleId)
            ->first();

        if ($existing) {
            return response()->json([
                'status'         => 'duplicate_ignored',
                'mysynctax_uuid' => $existing->unique_id
            ], 409);
        }
    
        // =====================================================
        // 5. TRANSACTION (HEADER + ITEMS)
        // =====================================================
        $idCon = DB::transaction(function () use (
            $payload, $uniqueId, $invoiceNo, $issueDate,
            $saleId, $items, $connCode, $client, $customer, $supplier, $item_clasification_code
        ) {
            
            // 🔥 HEADER: pricing kosong dulu
            $invoice_id = DB::table('invoice')->insertGetId([
                'invoice_no'               => $invoiceNo,
                'unique_id'                => $uniqueId,
                'sale_id_integrate'        => $saleId,
                'connection_integrate'     => $connCode,
                'id_developer'             => $client->id_developer,
                'id_customer'              => $customer->id_customer,
                'id_supplier'              => $supplier->id_customer,
    
                'invoice_status'           => 'Valid',
                'invoice_type_code'        => '11',
                'tax_category_id'          => '01',
                'tax_exemption_reason'     => '',
                'tax_scheme_id'            => 'OTH',
    
                'payment_note_term'        => data_get($payload, 'payment_note_term', 'CASH'),
                'payment_financial_account'=> '-',
                'payment_method'           => data_get($payload, 'payment_method', 'Cash'),
                'issue_date'               => $issueDate,
    
                // pricing kosong
                'price'                => 0,
                'total_price_discount' => 0,
                'taxable_amount'       => 0,
                'tax_amount'           => 0,
                'tax_percent'          => 0, // Removed tax percent to prevent double tax issues
                
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
    
            // ================= INSERT ITEMS =================
            $rows = [];
    
            foreach ($items as $index => $it) {
                $qty           = (float) data_get($it, 'invoiced_quantity', 0);
                $price         = (float) data_get($it, 'unit_price', 0);
                $discount      = (float) data_get($it, 'price_discount', 0);
                $itemTaxAmount = (float) data_get($it, 'tax_amount', 0); // Read exact tax amount from payload
    
                $lineBeforeTax = $qty * $price;
                $lineAfterDisc = $lineBeforeTax - $discount;
    
                $rows[] = [
                    'id_invoice'             => $invoice_id,
                    'sale_id_integrate'      => $saleId,
                    'item_id_integrate'      => data_get($payload, 'item_id'),
                    'connection_integrate'   => $connCode,
                    'unique_id'              => $uniqueId,
                    'id_developer'           => $client->id_developer,
                    'id_customer'            => $customer->id_customer,
                    'line_id'                => data_get($it, 'sorting_id', $index + 1),
                    'invoiced_quantity'      => $qty,
    
                    // sebelum tax
                    'line_extension_amount'  => $lineBeforeTax,
    
                    'item_description'       => data_get($it, 'item_description', 'Unnamed Item'),
                    'price_amount'           => $price,
                    'item_id_integrate'      => data_get($it, 'item_id', 0),
    
                    'price_discount'         => $discount,
                    'price_extension_amount' => $lineAfterDisc,
    
                    // Added tax_amount directly from the payload item
                    'tax'                      => $itemTaxAmount, 
                    'item_clasification_value' => $item_clasification_code,
    
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }
    
            DB::table('invoice_item')->insert($rows);
    
            return $invoice_id;
        });
    
        // =====================================================
        // 🔥 AUTO CALCULATE PRICING & TAX FROM ITEMS
        // =====================================================
        
        $totals = DB::table('invoice_item')
            ->where('id_invoice', $idCon)
            ->where('unique_id', $uniqueId)
            ->selectRaw('
                SUM(line_extension_amount) AS total_before_tax,
                SUM(price_discount)        AS total_discount,
                SUM(tax)                   AS total_tax_amount
            ')
            ->first();
    
        $totalBeforeTax = (float) $totals->total_before_tax;
        $totalDiscount  = (float) $totals->total_discount;
        $taxableAmount  = $totalBeforeTax - $totalDiscount;
        
        // Exclusively use sum of item taxes directly (No tax percent calculation)
        $totalTaxAmount = (float) $totals->total_tax_amount;
    
        $totalAmount = round($taxableAmount + $totalTaxAmount, 2);
    
        DB::table('invoice')
            ->where('id_invoice', $idCon)
            ->update([
                'price'                => $totalBeforeTax,
                'total_price_discount' => $totalDiscount,
                'taxable_amount'       => $taxableAmount,
                'tax_amount'           => $totalTaxAmount,
                'tax_percent'          => 0, // Enforce no tax percent
                'updated_at'           => now(),
            ]);
    
        // =====================================================
        // SUBMIT / RESPONSE
        // =====================================================
        
        session([
            'connection_integrate' => $connCode,
            'invoice_unique_id'    => $uniqueId,
            'consolidate_status'   => '',
            'invoice_type_code'    => '11'
        ]);
      
        $model = new \App\Models\eInvoisModel($connCode);
    
        $isAutoToLHDN = data_get($payload, 'isAutoToLHDN');
    
        if ($isAutoToLHDN == 1) {
            $result = $model->submit($idCon);
            $qr_lhdn = url('/qr_link/' . $uniqueId);
        } else {
            $qr_lhdn = 'No LHDN QR Link Provided';
            $result  = 'Please manually submit in system, since isAutoToLHDN = 0';
        }
      
        // 1. Assign the JSON response to a variable
        $response_json = response()->json([
            'status'          => 'ok',
            'invoice_id'      => $idCon,
            'mysynctax_uuid'  => $uniqueId,
            'customer_status' => $customerStatus,
            'qr_lhdn'         => $qr_lhdn,
            'customer_id'     => $customer->id_customer,
            'result'          => $result
        ], 201);

        // 2. Update the API log in the database
        DB::table('message_header')
            ->where('id', session('message_id'))
            ->update(['response_json' => $response_json->getContent()]);

        // 3. Clear the session
        session(['message_id' => '']);

        // 4. Return the response back to the API caller
        return $response_json;
    }

        public function note(Request $request, $mode = 'normal')
        {
  

   

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
               
                return response()->json([
                    'status'  => 'error',
                    'message' =>'Supplier email format invalid'
                ], 422);
            }

            if (!empty($supplier->phone) &&
                !preg_match('/^[0-9]{7,15}$/', $supplier->phone)) {
                //throw new \Exception('Supplier phone format invalid');
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Supplier phone format invalid'
                ], 422);
            
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
            $invoiceTypeCode = '12';
            $sign = -1;
            $label = 'Credit';
        } elseif ($noteType === 'debit') {
            $invoiceTypeCode = '13';
            $sign = 1;
            $label = 'Debit';
        } else {
            $invoiceTypeCode = '14';
            $sign = -1;
            $label = 'Refund';
        }


        if ($mode === 'general') {
            // invoice_generaltin → hanya TIN khas dibenarkan
            if (!in_array($tin_no, $blockedTIN)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This API only accepts General TIN (EI00000000010/20/30/40)'
                ], 422);
            }

            if ($tin_no === 'EI00000000010') {
               $item_clasification_code = '036';
               $identification_no = 'NA';
               $identification_type = 'BRN';

           } elseif ($tin_no === 'EI00000000020') {
               return response()->json([
                   'status'  => 'error',
                   'message' => 'This TIN No. not allowed for selfbill foreign supplier , please use EI00000000030'
               ], 422);

           } elseif ($tin_no === 'EI00000000030') {
               $item_clasification_code = '036';
               $identification_no = 'NA';
               $identification_type = 'BRN';

           } elseif ($tin_no === 'EI00000000040') {
               $item_clasification_code = '036';
               $identification_no = 'NA';
               $identification_type = 'BRN';
           }


           }

        /* =====================================================
           3. ORIGINAL INVOICE
        ===================================================== */
        $originalInvoiceId = data_get($payload, 'sale_id_integrate');

        if (!is_numeric($originalInvoiceId)) {
           

            return response()->json([
                'status'  => 'error',
                'message' => 'original_invoice_id must be numeric'
            ], 422);
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
               

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid previous invoice item reference'. $itemId
                ], 422);
            }

            $qty      = (float) data_get($item, 'qty', 0);
            $price    = (float) data_get($item, 'price', 0);
            $discount = (float) data_get($item, 'discount', 0);
            $tax      = (float) data_get($item, 'tax', 0);
            $desc     = data_get($item, 'description', '');

            /* ==== NUMERIC SANITY ==== */
            if ($qty <= 0 || $qty > 100000) {
               // throw new \Exception('Invalid qty value');
                return response()->json([
                    'status'  => 'error',
                    'message' =>'Invalid qty value'
                ], 422);
            }

            if ($price =='') {
               
                return response()->json([
                    'status'  => 'error',
                    'message' =>'Invalid price value'
                ], 422);
            }

         

           

            if (strlen($desc) > 500) {
                return response()->json([
                    'status'  => 'error',
                    'message' =>'Item description too long'
                ], 422);
               
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
        if(!$original->uuid){
            return response()->json([
                'status' => 'error',
                'message' => "This invoice not submited yet to LHDN"
            ], 400);
            exit();
            }

        session([

            'invoice_unique_id' => $uniqueId,
            'consolidate_status' => '',
            'previous_uuid' => $original->uuid,
            'previous_invoice_no' => $original->invoice_no,
            'invoice_type_code' => $invoiceTypeCode
        ]);
       

        $model = new \App\Models\eInvoisModel($connCode);
        $result=$model->submit($noteInvoiceId);
        session()->forget([
            'invoice_unique_id',
            'previous_uuid',
            'previous_invoice_no',
            'invoice_type_code'
        ]);


        DB::commit();


        try {
   
           
            $qr_lhdn = url('/qr_link/'.$uniqueId);
      
         
     
         // =====================================================
         // 8. RESPONSE
         // =====================================================
         return response()->json([
             'status'          => 'ok',
             'invoice_id'      => $noteInvoiceId,
             'note_type' => $noteType,
             'mysynctax_uuid'  => $uniqueId,
             'qr_lhdn'         => $qr_lhdn,   
             'result'          => $result
         ], 201);
    

    } catch (\Throwable $e) {

        DB::rollBack();

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
        exit();
    }
}

     }

