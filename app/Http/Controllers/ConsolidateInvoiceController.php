<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IntegrationInvoiceController extends Controller
{
    /**
     * Store invoice data from external API clients
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
    // Decode raw JSON (no validation)
    $payload = json_decode($request->getContent(), true);

    if (!is_array($payload)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Invalid JSON received'
        ], 400);
    }

    // Extract fields safely (no validation)
    $uniqueId     = data_get($payload, 'unique_id');
    $invoiceNo    = data_get($payload, 'invoice_no');
    $issueDate    = data_get($payload, 'issue_date');
    $saleId       = data_get($payload, 'sale_id_integrate');
    $totalItem    = data_get($payload, 'consolidate_total_item');
    $amountBefore = data_get($payload, 'consolidate_total_amount_before');
    $amountAfter  = data_get($payload, 'consolidate_total_amount_after');
    $items        = data_get($payload, 'items', []);

    // Connection code injected by IntegrateAuth middleware
    $connCode = $request->attributes->get('connection_integrate_code');

    // If missing essential keys, still continue but mark incomplete
    if (!$uniqueId || !$invoiceNo) {
        return response()->json([
            'status'  => 'error',
            'message' => 'unique_id and invoice_no are required'
        ], 400);
    }

    // === Idempotency check ===
    $existing = DB::table('consolidate_invoice')
        ->where('connection_integrate', $connCode)
        ->where('unique_id', $uniqueId)
        ->first();

    if ($existing) {
        return response()->json([
            'status' => 'duplicate_ignored',
            'id_consolidate_invoice' => $existing->id_invoice ?? null,
        ], 200);
    }

    // === Insert header + items ===
    $idConsolidate = DB::transaction(function () use (
        $payload, $uniqueId, $invoiceNo, $issueDate, $saleId,
        $totalItem, $amountBefore, $amountAfter, $items, $connCode, $request
    ) {

        // Insert main invoice
        $idCon = DB::table('consolidate_invoice')->insertGetId([
            'unique_id'                        => $uniqueId,
            'json_receive'                     => $request->getContent(),
            'consolidate_total_item'           => $totalItem,
            'consolidate_total_amount_before'  => $amountBefore,
            'consolidate_total_amount_after'   => $amountAfter,
            'sale_id_integrate'                => $saleId,
            'connection_integrate'             => $connCode,
            'invoice_no'                       => $invoiceNo,
            'issue_date'                       => $issueDate,
            'created_at'                       => now(),
            'updated_at'                       => now(),
        ]);

        // Prepare items
        $rows = [];
        foreach ($items as $it) {
            $rows[] = [
                'id_consolidate_invoice'   => $idCon,
                'connection_integrate'     => $connCode,
                'sale_id_integrate'        => data_get($it, 'sale_id_integrate', $saleId),
                'issue_date'               => $issueDate,
                'line_id'                  => data_get($it, 'line_id'),
                'invoiced_quantity'        => data_get($it, 'invoiced_quantity'),
                'line_extension_amount'    => data_get($it, 'line_extension_amount'),
                'item_description'         => data_get($it, 'item_description'),
                'price_amount'             => data_get($it, 'price_amount'),
                'price_discount'           => data_get($it, 'price_discount'),
                'price_extension_amount'   => data_get($it, 'price_extension_amount'),
                'item_clasification_type'  => data_get($it, 'item_clasification_type'),
                'item_clasification_value' => data_get($it, 'item_clasification_value'),
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }

        // Bulk insert
        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('consolidate_invoice_item')->insert($chunk);
            }
        }

        return $idCon;
    });

    return response()->json([
        'status' => 'ok',
        'id_consolidate_invoice' => $idConsolidate,
        'invoice_no' => $invoiceNo,
        'unique_id' => $uniqueId,
    ], 201);
}

}