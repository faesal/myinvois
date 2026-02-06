<?php

namespace App\Services\MyInvois\Template;

use Illuminate\Support\Facades\DB;
use Exception;

class TemplateScanner
{
    private array $mappingData = [];
    private array $existingPaths = [];
    private array $jsonPaths = [];

    public function scanJson($jsonString, $documentType = 'invoice', $version = '1.1')
    {
        $data = json_decode($jsonString, true);

        if (!$data) {
            throw new Exception('Invalid JSON');
        }

        if (!isset($data['Invoice'][0])) {
            throw new Exception('Invalid Invoice JSON structure');
        }

        // Get existing paths from database
        $this->existingPaths = $this->getExistingFieldPaths($documentType, $version);
        
        // Reset mapping data
        $this->mappingData = [];
        $this->jsonPaths = [];

        // Start scanning
        $this->scanRecursive(
            $data['Invoice'][0],
            $documentType,
            $version,
            ['Invoice', '0'],  // Start with base path segments
            0
        );

        // Save all mapping data to database
        $this->saveMappings($documentType, $version);
    }

    private function scanRecursive(
        array $data,
        string $documentType,
        string $version,
        array $pathSegments,  // Array of path segments
        int $arrayIndex = 0
    ) {
        foreach ($data as $key => $value) {
            // Build current path segments
            $currentPathSegments = $pathSegments;
            $currentPathSegments[] = $key;
            
            // For numeric keys (in repeatable sections), we need to add the index
            if (is_numeric($key) && $this->isNumericArray($data)) {
                // Replace the numeric key with the array index
                $currentPathSegments[count($currentPathSegments) - 1] = $arrayIndex;
            }
            
            // Build field path string
            $fieldPath = implode('.', $currentPathSegments);
            
            // Store this path for later comparison
            $this->jsonPaths[] = $fieldPath;

            // Check if this is a leaf node (actual value)
            if (!is_array($value) || (is_array($value) && isset($value['_']))) {
                // This is a leaf node (actual value)
                $defaultValue = $this->extractValue($value);
                $isLeaf = true;
            } else {
                $defaultValue = null;
                $isLeaf = false;
            }

            // Get parent path (remove last segment)
            $parentPathSegments = array_slice($currentPathSegments, 0, -1);
            $parentPath = implode('.', $parentPathSegments);
            
            // Determine child name (the last segment)
            $childName = end($currentPathSegments);
            
            // Store mapping information
            $this->storeMapping(
                $documentType,
                $version,
                $parentPath,
                $key,  // Original key
                $arrayIndex,
                $defaultValue,
                $fieldPath,
                'invoice', // Default table name
                $this->generateColumnName($key, $fieldPath),
                $this->isLoopingField($key, $value) ? 'true' : 'false'
            );

            // If this is a leaf node with value '_', also store the actual value path
            if (is_array($value) && isset($value['_'])) {
                $valuePathSegments = $currentPathSegments;
                $valuePathSegments[] = '_';
                $valueFieldPath = implode('.', $valuePathSegments);
                
                // Store value path
                $this->jsonPaths[] = $valueFieldPath;
                
                $this->storeMapping(
                    $documentType,
                    $version,
                    $fieldPath,
                    '_',
                    0,
                    $value['_'],
                    $valueFieldPath,
                    'invoice',
                    $this->generateColumnName('value', $fieldPath),
                    'false'
                );
                
                // Also store any attributes if present
                foreach ($value as $attrKey => $attrValue) {
                    if ($attrKey !== '_' && !is_array($attrValue)) {
                        $attrPathSegments = $currentPathSegments;
                        $attrPathSegments[] = $attrKey;
                        $attrFieldPath = implode('.', $attrPathSegments);
                        
                        // Store attribute path
                        $this->jsonPaths[] = $attrFieldPath;
                        
                        $this->storeMapping(
                            $documentType,
                            $version,
                            $fieldPath,
                            $attrKey,
                            0,
                            $attrValue,
                            $attrFieldPath,
                            'invoice',
                            $this->generateColumnName($attrKey, $fieldPath),
                            'false'
                        );
                    }
                }
            }

            // Continue recursion for nested arrays
            if (is_array($value) && !isset($value['_'])) {
                // Check if this is a numeric array (repeatable)
                if ($this->isNumericArray($value)) {
                    foreach ($value as $index => $item) {
                        if (is_array($item)) {
                            // For repeatable sections, add index to path
                            $newPathSegments = $currentPathSegments;
                            $newPathSegments[] = $index;
                            
                            $this->scanRecursive(
                                $item,
                                $documentType,
                                $version,
                                $newPathSegments,
                                $index
                            );
                        }
                    }
                } else {
                    $this->scanRecursive(
                        $value,
                        $documentType,
                        $version,
                        $currentPathSegments,
                        0
                    );
                }
            }
        }
    }

    private function storeMapping(
        string $documentType,
        string $version,
        string $parent,
        string $child,
        int $childNo,
        $defaultValue,
        string $fieldPath,
        string $tableName,
        string $columnName,
        string $isLooping
    ) {
        // Check if this path already exists in our mapping data (avoid duplicates)
        foreach ($this->mappingData as $mapping) {
            if ($mapping['field_path'] === $fieldPath) {
                return;
            }
        }

        $this->mappingData[] = [
            'document_type' => $documentType,
            'version' => $version,
            'parent' => $parent,
            'child' => $child,
            'child_no' => $childNo,
            'default_value' => $defaultValue,
            'field_path' => $fieldPath,
            'table_name' => $tableName,
            'column_name' => $columnName,
            'condition' => null,
            'is_looping' => $isLooping,
            'field_type' => null
        ];
    }

    private function getExistingFieldPaths(string $documentType, string $version): array
    {
        $paths = DB::table('document_field_mapping')
            ->where('document_type', $documentType)
            ->where('version', $version)
            ->pluck('field_path')
            ->toArray();
            
        return array_flip($paths); // Use flip for faster lookup
    }

    private function extractValue($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        
        if (isset($value['_'])) {
            return $value['_'];
        }
        
        return null;
    }

    private function saveMappings(string $documentType, string $version)
    {
        /*foreach ($this->mappingData as $mapping) {
            // Check if mapping already exists in database
            if (isset($this->existingPaths[$mapping['field_path']])) {
                // Update existing mapping
                DB::table('document_field_mapping')
                    ->where('version', $version)
                    ->where('document_type', $documentType)
                    ->where('field_path', $mapping['field_path'])
                    ->update($mapping);
            } else {
                // Insert new mapping
                DB::table('document_field_mapping')->insert($mapping);
            }

            // Also insert/update document_field table
            $this->updateDocumentFieldTable($mapping);
        }*/
    }

    private function updateDocumentFieldTable(array $mapping)
    {
        $fieldExists = DB::table('document_field')
            ->where('document_type', $mapping['document_type'])
            ->where('version', $mapping['version'])
            ->where('field_path', $mapping['field_path'])
            ->exists();

       /* if (!$fieldExists) {
            DB::table('document_field')->insert([
                'document_type' => $mapping['document_type'],
                'version' => $mapping['version'],
                'field_path' => $mapping['field_path'],
                'field_label' => $mapping['child'],
                'parent_path' => $mapping['parent'],
                'child_path' => $mapping['child'],
                'child_no' => $mapping['child_no'],
                'is_repeatable' => $mapping['is_looping'] === 'true' ? 1 : 0,
                'created_at' => now()
            ]);
        } else {
            DB::table('document_field')
                ->where('document_type', $mapping['document_type'])
                ->where('version', $mapping['version'])
                ->where('field_path', $mapping['field_path'])
                ->update([
                    'field_label' => $mapping['child'],
                    'parent_path' => $mapping['parent'],
                    'child_path' => $mapping['child'],
                    'child_no' => $mapping['child_no'],
                    'is_repeatable' => $mapping['is_looping'] === 'true' ? 1 : 0,
                ]);
        }*/
    }

    private function generateColumnName(string $key, string $fullPath): string
    {
        // Remove special characters and convert to snake_case
        $columnName = str_replace(['.', '[', ']', '-', ' '], '_', $fullPath);
        $columnName = str_replace('__', '_', $columnName);
        $columnName = trim($columnName, '_');
        
        // If key is '_', append 'value'
        if ($key === '_') {
            $columnName .= '_value';
        }
        
        return strtolower($columnName);
    }

    private function isLoopingField(string $key, $value): bool
    {
        // Check if this field represents a loop/repeatable section
        $loopingKeys = [
            'InvoiceLine', 'AllowanceCharge', 'AdditionalDocumentReference', 
            'PartyIdentification', 'UBLExtension', 'ExtensionContent',
            'UBLDocumentSignatures', 'SignatureInformation', 'Signature',
            'TaxSubtotal', 'TaxCategory', 'TaxScheme', 'ID',
            'AddressLine', 'Line', 'Contact', 'PostalAddress',
            'Delivery', 'DeliveryParty', 'Shipment', 'FreightAllowanceCharge',
            'PaymentMeans', 'PayeeFinancialAccount', 'PaymentTerms',
            'PrepaidPayment', 'LegalMonetaryTotal', 'TaxTotal',
            'AccountingSupplierParty', 'AccountingCustomerParty', 'Party',
            'IndustryClassificationCode', 'PartyLegalEntity', 'CommodityClassification',
            'ItemClassificationCode', 'OriginCountry', 'Price', 'ItemPriceExtension',
            'Cert', 'CertDigest', 'DigestMethod', 'DigestValue', 'IssuerSerial',
            'X509IssuerName', 'X509SerialNumber', 'KeyInfo', 'X509Data',
            'X509Certificate', 'X509SubjectName', 'X509IssuerSerial',
            'SignedInfo', 'Reference', 'QualifyingProperties', 'SignedProperties',
            'SignedSignatureProperties', 'SigningTime', 'SigningCertificate'
        ];
        
        if (in_array($key, $loopingKeys)) {
            return true;
        }
        
        // Also check if value is a numeric array
        return is_array($value) && $this->isNumericArray($value);
    }

    private function isNumericArray(array $array): bool
    {
        if (count($array) === 0) return false;
        
        // Check if all keys are numeric and sequential starting from 0
        $keys = array_keys($array);
        return $keys === range(0, count($array) - 1);
    }

    /**
     * Find missing segments by comparing JSON with existing field mappings
     */
    public function findMissingSegments(string $documentType, string $version): array
    {
        $missingSegments = [];
        
        // Get all paths from JSON (from the last scan)
        $jsonPaths = array_unique($this->jsonPaths);
        
        // Get all paths from database
        $dbPaths = array_keys($this->existingPaths);
        
        // Find paths in JSON that are not in database
        $missingInDb = array_diff($jsonPaths, $dbPaths);
        
        // Find paths in database that are not in JSON (might be deprecated)
        $missingInJson = array_diff($dbPaths, $jsonPaths);
        
        // Analyze missing paths to provide better insights
        foreach ($missingInDb as $path) {
            $segments = explode('.', $path);
            $lastSegment = end($segments);
            $parentPath = implode('.', array_slice($segments, 0, -1));
            
            $missingSegments['missing_in_database'][] = [
                'field_path' => $path,
                'parent_path' => $parentPath,
                'child' => $lastSegment,
                'type' => $this->determinePathType($path, $lastSegment),
                'suggested_table' => $this->suggestTable($path),
                'suggested_column' => $this->generateColumnName($lastSegment, $path)
            ];
        }
        
        foreach ($missingInJson as $path) {
            $segments = explode('.', $path);
            $lastSegment = end($segments);
            $parentPath = implode('.', array_slice($segments, 0, -1));
            
            $missingSegments['missing_in_json'][] = [
                'field_path' => $path,
                'parent_path' => $parentPath,
                'child' => $lastSegment,
                'type' => $this->determinePathType($path, $lastSegment),
                'status' => 'possibly_deprecated'
            ];
        }
        
        return $missingSegments;
    }

    /**
     * Determine the type of path for better categorization
     */
    private function determinePathType(string $path, string $lastSegment): string
    {
        if ($lastSegment === '_') {
            return 'value_field';
        }
        
        if (is_numeric($lastSegment)) {
            return 'array_index';
        }
        
        $attributePatterns = [
            'schemeID', 'schemeAgencyID', 'schemeAgencyName', 'listID', 
            'listVersionID', 'listAgencyID', 'name', 'unitCode', 'currencyID',
            'Algorithm', 'Id', 'Target', 'Type', 'URI'
        ];
        
        if (in_array($lastSegment, $attributePatterns)) {
            return 'attribute';
        }
        
        return 'element';
    }

    /**
     * Suggest a table name based on the path
     */
    private function suggestTable(string $path): string
    {
        $pathLower = strtolower($path);
        
        if (str_contains($pathLower, 'invoiceline') || str_contains($pathLower, 'item')) {
            return 'invoice_item';
        }
        
        if (str_contains($pathLower, 'accountingsupplierparty') || 
            str_contains($pathLower, 'accountingcustomerparty') ||
            str_contains($pathLower, 'delivery') ||
            str_contains($pathLower, 'party')) {
            return 'customer';
        }
        
        if (str_contains($pathLower, 'allowancecharge') || 
            str_contains($pathLower, 'taxtotal') ||
            str_contains($pathLower, 'legalmonetarytotal')) {
            return 'invoice_summary';
        }
        
        return 'invoice';
    }

    /**
     * Get new mappings that were not previously in the database
     */
    public function getNewMappings(string $documentType, string $version): array
    {
        $allMappings = $this->getMappingFromDB($documentType, $version);
        $existingPaths = $this->getExistingFieldPaths($documentType, $version);
        
        return array_filter($allMappings, function($mapping) use ($existingPaths) {
            return !isset($existingPaths[$mapping->field_path]);
        });
    }

    /**
     * Get mapping data from database for testing
     */
    public function getMappingFromDB(string $documentType, string $version): array
    {
        return DB::table('document_field_mapping')
            ->where('document_type', $documentType)
            ->where('version', $version)
            ->orderBy('field_path')
            ->get()
            ->toArray();
    }

    /**
     * Compare JSON file with database and return detailed missing segments
     * This is the main function you requested
     */
    public function compareJsonWithDb(string $jsonString, string $documentType = 'invoice', string $version = '1.1'): array
    {
        $data = json_decode($jsonString, true);

        if (!$data) {
            throw new Exception('Invalid JSON');
        }

        if (!isset($data['Invoice'][0])) {
            throw new Exception('Invalid Invoice JSON structure');
        }

        // Get existing paths from database
        $this->existingPaths = $this->getExistingFieldPaths($documentType, $version);
        
        // Reset JSON paths
        $this->jsonPaths = [];

        // Scan the JSON to collect all paths (without saving to database)
        $this->collectJsonPaths(
            $data['Invoice'][0],
            ['Invoice', '0']
        );

        // Get unique paths
        $jsonPaths = array_unique($this->jsonPaths);
        $dbPaths = array_keys($this->existingPaths);

        // Find differences
        $missingInDb = array_diff($jsonPaths, $dbPaths);
        $missingInJson = array_diff($dbPaths, $jsonPaths);

        // Format the results
        $results = [
            'summary' => [
                'json_paths_count' => count($jsonPaths),
                'db_paths_count' => count($dbPaths),
                'missing_in_db_count' => count($missingInDb),
                'missing_in_json_count' => count($missingInJson),
            ],
            'missing_in_database' => $this->formatMissingPaths($missingInDb, 'json'),
            'missing_in_json' => $this->formatMissingPaths($missingInJson, 'db')
        ];

        return $results;
    }

    /**
     * Collect all paths from JSON without creating mappings
     */
    private function collectJsonPaths(array $data, array $pathSegments)
    {
        foreach ($data as $key => $value) {
            // Build current path segments
            $currentPathSegments = $pathSegments;
            $currentPathSegments[] = $key;
            
            // For numeric keys, replace with index
            if (is_numeric($key) && $this->isNumericArray($data)) {
                $currentPathSegments[count($currentPathSegments) - 1] = 0; // Use 0 as index for comparison
            }
            
            // Build field path string
            $fieldPath = implode('.', $currentPathSegments);
            $this->jsonPaths[] = $fieldPath;

            // Handle value field
            if (is_array($value) && isset($value['_'])) {
                $valueFieldPath = $fieldPath . '._';
                $this->jsonPaths[] = $valueFieldPath;
                
                // Handle attributes
                foreach ($value as $attrKey => $attrValue) {
                    if ($attrKey !== '_' && !is_array($attrValue)) {
                        $attrFieldPath = $fieldPath . '.' . $attrKey;
                        $this->jsonPaths[] = $attrFieldPath;
                    }
                }
            }

            // Continue recursion for nested arrays
            if (is_array($value) && !isset($value['_'])) {
                if ($this->isNumericArray($value)) {
                    // For arrays, just check the first element
                    if (isset($value[0]) && is_array($value[0])) {
                        $newPathSegments = $currentPathSegments;
                        $newPathSegments[] = 0;
                        $this->collectJsonPaths($value[0], $newPathSegments);
                    }
                } else {
                    $this->collectJsonPaths($value, $currentPathSegments);
                }
            }
        }
    }

    /**
     * Format missing paths with detailed information
     */
    private function formatMissingPaths(array $paths, string $source): array
    {
        $formatted = [];
        
        foreach ($paths as $path) {
            $segments = explode('.', $path);
            $lastSegment = end($segments);
            $parentPath = implode('.', array_slice($segments, 0, -1));
            
            $formatted[] = [
                'field_path' => $path,
                'parent_path' => $parentPath,
                'child' => $lastSegment,
                'type' => $this->determinePathType($path, $lastSegment),
                'source' => $source,
                'suggested_action' => $source === 'json' ? 'add_to_db' : 'review_deprecation'
            ];
        }
        
        return $formatted;
    }
}