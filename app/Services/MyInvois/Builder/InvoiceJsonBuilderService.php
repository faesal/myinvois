<?php

namespace App\Services\MyInvois\Builder;

use Illuminate\Support\Facades\DB;

class InvoiceJsonBuilderService
{
    /**
     * Build invoice JSON from template + DB mapping
     */
    public function build(int $invoiceId, string $version = '1.1'): array
    {
        // 1. Load invoice.json template
        $jsonPath = base_path("app/Services/MyInvois/Templates/invoice.json");
        $jsonContent = json_decode(file_get_contents($jsonPath), true);

        // 2. Load invoice (main anchor)
        $invoice = DB::table('invoice')
            ->select(
                '*',
                DB::raw('(taxable_amount + tax_amount) as price_total')
            )
            ->where('id_invoice', $invoiceId)
            ->first();
                
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        // 3. Load customer
        if (in_array($invoice->invoice_type_code, ['01', '02', '03', '04'])) {
            $customer = DB::table('customer')
                ->where('id_customer', $invoice->id_customer)
                ->where('customer_type', "CUSTOMER")
                ->first();
            
            $supplier = DB::table('customer')
                ->where('id_customer', $invoice->id_supplier)
                ->where('customer_type', "SUPPLIER")
                ->first();
        } else {
            $customer = DB::table('customer')
                ->where('id_customer', $invoice->id_customer)
                ->where('customer_type', "SUPPLIER")
                ->first();
            
            $supplier = DB::table('customer')
                ->where('id_customer', $invoice->id_supplier)
                ->where('customer_type', "CUSTOMER")
                ->first();
        }

        // 4. Load invoice items
        $items = DB::table('invoice_item')
            ->where('id_invoice', $invoiceId)
            ->get();

        // 5. Load mapping table
        $mappings = DB::table('document_field_mapping')
            ->where('document_type', 'invoice')
            ->where('version', $version)
            ->get();

        // 6. Initialize InvoiceLine array
        if (!isset($jsonContent['Invoice'][0]['InvoiceLine'])) {
            $jsonContent['Invoice'][0]['InvoiceLine'] = [];
        }

        // 7. Process invoice items first to create the structure
        $documentLines = [];
        foreach ($items as $itemIndex => $item) {
            $invoiceLine = $this->createInvoiceLineFromItem($item, $invoice);
            $jsonContent['Invoice'][0]['InvoiceLine'][$itemIndex] = $invoiceLine;
        }

        // 8. Apply mapping for non-item fields
        foreach ($mappings as $map) {
            // Skip invoice_item mappings as we've already processed them
            if ($map->table_name === 'invoice_item') {
                continue;
            }

            // Skip if column_name is null
            if ($map->column_name === null) {
                continue;
            }

            $value = $this->resolveValue(
                $map->table_name,
                $map->column_name,
                $invoice,
                $customer,
                $items,
                $supplier
            );

            // Use default_value if DB value is null or empty
            if ($value === null || $value === '') {
                if ($map->default_value !== null && $map->default_value !== '') {
                    $value = $map->default_value;
                }
            }

            if ($value !== null) {
                // Format time fields
                if ($map->field_type === 'time') {
                    try {
                        $dt = new \DateTime($value);
                        $value = $dt->format('H:i:s') . 'Z';
                    } catch (\Exception $e) {
                        // Keep original value
                    }
                }

                // Format date fields
                if ($map->field_type === 'date') {
                    try {
                        $dt = new \DateTime($value);
                        $value = $dt->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Keep original value
                    }
                }

                $this->updateValueByKey(
                    $jsonContent,
                    $map->field_path,
                    $value
                );
            }
        }

        return $jsonContent;
    }

    /**
     * Create an InvoiceLine structure from database item
     */
    protected function createInvoiceLineFromItem($item, $invoice): array
    {
        // Calculate multiplier factor (percentage) for AllowanceCharge
        $multiplierFactor = 1;
      //  if ($item->price_amount > 0 && $item->price_discount > 0) {
      //      $multiplierFactor = ($item->price_discount / $item->price_amount);
        //}

        // Get tax percentage from invoice - convert from decimal to percentage
        $taxPercent = $invoice->tax_percent ?? 0;
        
        // Calculate tax amount for this line - use price_extension_amount if available, otherwise use price_amount
        $taxableAmount = $invoice->taxable_amount ;
        
        // If tax field exists in item, use it directly, otherwise calculate from taxPercent
        $taxAmount = $item->tax;

        // Create AllowanceCharge array based on sample code
        $allowanceCharges = [];
        
        // Only add allowance charge if there's a discount
        if ($item->price_discount > 0) {
            $allowanceCharge = [
                'ChargeIndicator' => [
                    ['_' => true] // true means it's a charge (discount)
                ],
                'AllowanceChargeReason' => [
                    ['_' => $item->item_description ?? 'Discount']
                ],
                'MultiplierFactorNumeric' => [
                    ['_' => (float)$multiplierFactor]
                ],
                'Amount' => [
                    [
                        '_' => (float)($item->price_discount ?? 0),
                        'currencyID' => $invoice->document_currency_code ?? 'MYR'
                    ]
                ]
            ];
            $allowanceCharges[] = $allowanceCharge;
        }

        // Create Item structure
        $itemStructure = [
            'Description' => [
                ['_' => $item->item_description ?? '']
            ],
            'CommodityClassification' => []
        ];

        // Add first CommodityClassification (PTC) - from sample code
        $itemStructure['CommodityClassification'][] = [
            'ItemClassificationCode' => [
                [
                    '_' =>  '12344321',
                    'listID' => 'PTC'
                ]
            ]
        ];

        // Add second CommodityClassification (CLASS) - from sample code
        if (!empty($item->item_clasification_type)) {
            $itemStructure['CommodityClassification'][] = [
                'ItemClassificationCode' => [
                    [
                        '_' => (string)$item->item_clasification_type,
                        'listID' => 'CLASS'
                    ]
                ]
            ];
        }

        // Create TaxTotal structure - based on sample code
        $taxScheme = [
            'ID' => [
                [
                    '_' => 'OTH',
                    'schemeID' => 'UN/ECE 5153',
                    'schemeAgencyID' => '6'
                ]
            ]
        ];

        $taxCategory = [
            'ID' => [
                ['_' => '01']
            ],
            'Percent' => [
                ['_' => (float)$taxPercent]
            ],
            'TaxExemptionReason' => [
                ['_' => $invoice->tax_exemption_reason ?? 'SST']
            ],
            'TaxScheme' => [
                $taxScheme
            ]
        ];

        $taxSubtotal = [
            'TaxableAmount' => [
                [
                    '_' => (float)$taxableAmount,
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ],
            'TaxAmount' => [
                [
                    '_' => (float)$taxAmount,
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ],
            'Percent' => [
                ['_' => (float)$taxPercent]
            ],
            'TaxCategory' => [
                $taxCategory
            ]
        ];

        $taxTotal = [
            'TaxAmount' => [
                [
                    '_' => (float)$taxAmount,
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ],
            'TaxSubtotal' => [
                $taxSubtotal
            ]
        ];

        // Create Price structure
        $price = [
            'PriceAmount' => [
                [
                    '_' => (float)($item->price_amount ?? 0),
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ]
        ];

        // Create ItemPriceExtension - based on sample code
        $itemPriceExtension = [
            'Amount' => [
                [
                    '_' => (float)($item->price_extension_amount ?? $item->line_extension_amount ?? 0),
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ]
        ];

        // Build final InvoiceLine structure
        return [
            'ID' => [
                ['_' => $item->line_id ?? $item->item_id_integrate ?? $invoice->invoice_no ?? '']
            ],
            'InvoicedQuantity' => [
                [
                    '_' => (float)($item->invoiced_quantity ?? 0),
                    'unitCode' => 'C62'
                ]
            ],
            'LineExtensionAmount' => [
                [
                    '_' => (float)($item->line_extension_amount ?? 0),
                    'currencyID' => $invoice->document_currency_code ?? 'MYR'
                ]
            ],
            'AllowanceCharge' => $allowanceCharges,
            'TaxTotal' => [$taxTotal],
            'Item' => [$itemStructure],
            'Price' => [$price],
            'ItemPriceExtension' => [$itemPriceExtension]
        ];
    }

    /**
     * Handle invoice item mappings (for backward compatibility)
     */
    protected function handleInvoiceItemMapping(array &$json, $map, $items)
    {
        // This method is kept for backward compatibility
        // but the main logic now uses createInvoiceLineFromItem
    }
    
    /**
     * Update a single invoice line item (for backward compatibility)
     */
    protected function updateSingleInvoiceLine(array &$invoiceLines, $itemIndex, $subSegments, $column, $item, $map)
    {
        // This method is kept for backward compatibility
    }
    
    /**
     * Create empty invoice line structure (simplified version)
     */
    protected function createEmptyInvoiceLineStructure(): array
    {
        return $this->createInvoiceLineFromItem((object)[], (object)[]);
    }

    /**
     * Resolve value from DB source
     */
    protected function resolveValue(
        string $table,
        string $column,
        $invoice,
        $customer,
        $items,
        $supplier
    ) {
        switch ($table) {
            case 'invoice':
                return $invoice->{$column} ?? null;

            case 'customer':
                return $customer->{$column} ?? null;
            
            case 'supplier':
                return $supplier->{$column} ?? null;

            case 'invoice_item':
                // For non-wildcard paths
                return null;

            default:
                return null;
        }
    }

    /**
     * Update JSON value using dot / array path with numeric indices
     */
    protected function updateValueByKey(
        array &$json,
        string $path,
        $value
    ) {
        $segments = explode('.', $path);
        $current = &$json;

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $segment = (int)$segment;
                if (!is_array($current) || !isset($current[$segment])) {
                    return;
                }
                $current = &$current[$segment];
            } else {
                if (!isset($current[$segment])) {
                    return;
                }
                $current = &$current[$segment];
            }
        }
        
        $current = $value;
    }
}