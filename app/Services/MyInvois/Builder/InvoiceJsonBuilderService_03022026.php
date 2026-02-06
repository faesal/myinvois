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
        if($invoice->invoice_type_code==01 ||$invoice->invoice_type_code==02 || $invoice->invoice_type_code==03 || $invoice->invoice_type_code==04){
        $customer = DB::table('customer')
            ->where('id_customer', $invoice->id_customer)
            ->where('customer_type', "CUSTOMER")
            ->first();
        
        $supplier = DB::table('customer')
            ->where('id_customer', $invoice->id_supplier)
            ->where('customer_type', "SUPPLIER")
            ->first();
        }else{
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

        // 6. Apply mapping
        foreach ($mappings as $map) {
            // Check if this is an invoice_item mapping
            if ($map->table_name === 'invoice_item') {
                // Handle invoice items (array of items)
                $this->handleInvoiceItemMapping($jsonContent, $map, $items);
            } else {
                // Skip if column_name is null (like in row 41 where it's a parent node)
                if ($map->column_name === null) {
                    continue; // Skip parent nodes that don't have a column to map
                }

                $value = $this->resolveValue(
                    $map->table_name,
                    $map->column_name, // Now guaranteed to be a string
                    $invoice,
                    $customer,
                    $items,
                    $supplier
                );

                // ✅ USE default_value if DB value is null or empty
                if ($value === null || $value === '') {
                    if ($map->default_value !== null && $map->default_value !== '') {
                        $value = $map->default_value;
                    }
                }

                if ($value !== null) {
                    // ===== TIME FORMAT HANDLER (ONLY for field_type = time) =====
                    if ($map->field_type === 'time') {
                        try {
                            $dt = new \DateTime($value);
                            $value = $dt->format('H:i:s') . 'Z';
                        } catch (\Exception $e) {
                            // Keep original value if formatting fails
                        }
                    }

                    // DATE
                    if ($map->field_type === 'date') {
                        try {
                            $dt = new \DateTime($value);
                            $value = $dt->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Keep original value if formatting fails
                        }
                    }

                    $this->updateValueByKey(
                        $jsonContent,
                        $map->field_path,
                        $value
                    );
                }
            }
        }

        return $jsonContent;
    }

    /**
     * Handle invoice item mappings (array of items)
     */
    protected function handleInvoiceItemMapping(array &$json, $map, $items)
{
    // Skip if column_name is null
    if ($map->column_name === null || empty($map->column_name)) {
        return;
    }

    $path = $map->field_path;
    
    // Check if path contains InvoiceLine (invoice items)
    if (str_contains($path, 'InvoiceLine')) {
        $segments = explode('.', $path);
        
        // Find InvoiceLine segment position
        $invoiceLineIndex = -1;
        foreach ($segments as $i => $segment) {
            if ($segment === 'InvoiceLine') {
                $invoiceLineIndex = $i;
                break;
            }
        }
        
        if ($invoiceLineIndex === -1) {
            return; // No InvoiceLine found in path
        }
        
        // Check if there's a specific index after InvoiceLine
        if (isset($segments[$invoiceLineIndex + 1]) && is_numeric($segments[$invoiceLineIndex + 1])) {
            // Specific index path like "Invoice.0.InvoiceLine.0.ID.0._"
            $itemIndex = (int)$segments[$invoiceLineIndex + 1];
            
            if (isset($items[$itemIndex])) {
                $value = $items[$itemIndex]->{$map->column_name} ?? null;
                if ($value !== null) {
                    $this->updateValueByKey($json, $path, $value);
                }
            }
        } else {
            // Handle wildcard or all invoice items
            // The database doesn't show wildcard paths, but we should handle if needed
            if (isset($segments[$invoiceLineIndex + 1]) && $segments[$invoiceLineIndex + 1] === '*') {
                // Wildcard path like "Invoice.0.InvoiceLine.*.ID.0._"
                $remainingPath = implode('.', array_slice($segments, $invoiceLineIndex + 2));
                
                // Ensure we have InvoiceLine array in JSON
                if (isset($json['Invoice'][0]['InvoiceLine']) && is_array($json['Invoice'][0]['InvoiceLine'])) {
                    foreach ($items as $itemIndex => $item) {
                        if (isset($json['Invoice'][0]['InvoiceLine'][$itemIndex])) {
                            $value = $item->{$map->column_name} ?? null;
                            if ($value !== null) {
                                $current = &$json['Invoice'][0]['InvoiceLine'][$itemIndex];
                                $remainingSegments = explode('.', $remainingPath);
                                
                                foreach ($remainingSegments as $segment) {
                                    if (is_numeric($segment)) {
                                        $segment = (int)$segment;
                                        if (!isset($current[$segment])) {
                                            // Create array if numeric segment doesn't exist
                                            if (is_array($current) && $segment === count($current)) {
                                                $current[$segment] = [];
                                            } else {
                                                break;
                                            }
                                        }
                                        $current = &$current[$segment];
                                    } else {
                                        if (!isset($current[$segment])) {
                                            // Create array for non-numeric segment
                                            $current[$segment] = [];
                                        }
                                        $current = &$current[$segment];
                                    }
                                }
                                
                                // Set the value
                                if (is_array($current) && count($current) === 1 && isset($current[0])) {
                                    // Handle array with single element (like the JSON structure)
                                    if (is_array($current[0]) && isset($current[0]['_'])) {
                                        $current[0]['_'] = $value;
                                    } else {
                                        $current[0] = $value;
                                    }
                                } else {
                                    $current = $value;
                                }
                            }
                        }
                    }
                }
            } else {
                // No index specified - handle first item or create new structure
                // Based on database, paths like "Invoice.0.InvoiceLine.ID.0._" should have index
                // This might be an error in mapping, but we'll handle gracefully
                if (!empty($items)) {
                    $value = $items[0]->{$map->column_name} ?? null;
                    if ($value !== null) {
                        // Try to find or create the structure
                        $this->updateValueByKey($json, $path, $value);
                    }
                }
            }
        }
    }
}

    /**
     * Resolve value from DB source
     */
    protected function resolveValue(
        string $table,
        string $column, // Now guaranteed to be a string
        $invoice,
        $customer,
        $items,
        $supplier
    ) {
       

        switch ($table) {
            case 'invoice':
                return $invoice->{$column} ?? null;

            case 'customer':
               // echo "resolveValue - table: {$table}, column: {$column}";
                return $customer->{$column} ?? null;
            
            case 'supplier':
                return $supplier->{$column} ?? null;

            case 'invoice_item':
                // For non-wildcard paths, this might be called for specific item indices
                // The actual handling is done in handleInvoiceItemMapping
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