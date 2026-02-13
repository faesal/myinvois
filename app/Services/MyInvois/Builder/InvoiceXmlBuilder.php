<?php

namespace App\Services\MyInvois\Builder;

class InvoiceXmlBuilder
{
    protected string $templatePath;
    
    public function __construct()
    {
        $this->templatePath = base_path('app/Services/MyInvois/Templates/invoice.xml');
    }

    public function build(array $json): string
    {
        // Load XML template
        $xml = new \DOMDocument();
        $xml->load($this->templatePath);
        
        // Get invoice data from JSON
        $invoice = $json['Invoice'][0];
        
        // Process the invoice data and update XML
        $this->updateXmlFromJson($xml, $invoice);
        
        return $xml->saveXML();
    }
    
    protected function updateAdditionalDocumentReferences(\DOMXPath $xpath, array $data): void
    {
        if (isset($data['AdditionalDocumentReference'])) {
            // Remove existing AdditionalDocumentReference elements (keep only first 5 from template)
            $existingRefs = $xpath->query("//cac:AdditionalDocumentReference");
            // Keep first 5 as per template structure
            for ($i = 5; $i < $existingRefs->length; $i++) {
                if ($existingRefs->item($i)) {
                    $existingRefs->item($i)->parentNode->removeChild($existingRefs->item($i));
                }
            }
            
            // Update existing ones
            $index = 0;
            foreach ($data['AdditionalDocumentReference'] as $refData) {
                if ($index < 5) { // Update only first 5 as per template
                    $xpathIndex = $index + 1;
                    $this->updateNode($xpath, 
                        "//cac:AdditionalDocumentReference[$xpathIndex]/cbc:ID", 
                        $refData['ID'] ?? []);
                    
                    if (isset($refData['DocumentType'])) {
                        $this->updateNode($xpath, 
                            "//cac:AdditionalDocumentReference[$xpathIndex]/cbc:DocumentType", 
                            $refData['DocumentType']);
                    }
                    
                    if (isset($refData['DocumentDescription'])) {
                        $this->updateNode($xpath, 
                            "//cac:AdditionalDocumentReference[$xpathIndex]/cbc:DocumentDescription", 
                            $refData['DocumentDescription']);
                    }
                }
                $index++;
            }
        }
    }

    protected function updateXmlFromJson(\DOMDocument $xml, array $invoiceData): void
    {
        // Create XPath for easier navigation
        $xpath = new \DOMXPath($xml);
        
        // Register namespaces
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('sac', 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2');
        $xpath->registerNamespace('', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        
        // Update basic fields
        $this->updateBasicFields($xpath, $invoiceData);
        
        // Update AdditionalDocumentReference
        $this->updateAdditionalDocumentReferences($xpath, $invoiceData);
        
        // Update AccountingSupplierParty
        if (isset($invoiceData['AccountingSupplierParty'])) {
            $this->updateAccountingParty($xpath, 'AccountingSupplierParty', $invoiceData['AccountingSupplierParty'][0]);
        }
        
        // Update AccountingCustomerParty
        if (isset($invoiceData['AccountingCustomerParty'])) {
            $this->updateAccountingParty($xpath, 'AccountingCustomerParty', $invoiceData['AccountingCustomerParty'][0]);
        }
        
        // Update Delivery
        if (isset($invoiceData['Delivery'])) {
            $this->updateDelivery($xpath, $invoiceData['Delivery'][0]);
        }
        
        // Update PaymentMeans
        if (isset($invoiceData['PaymentMeans'])) {
            $this->updatePaymentMeans($xpath, $invoiceData['PaymentMeans'][0]);
        }
        
        // Update TaxTotal
       
            $this->updateTaxTotal($xpath, $invoiceData['TaxTotal'][0]);
    
        
        // Update LegalMonetaryTotal
        if (isset($invoiceData['LegalMonetaryTotal'])) {
            $this->updateLegalMonetaryTotal($xpath, $invoiceData['LegalMonetaryTotal'][0]);
        }
        

        if (isset($invoiceData['InvoiceLine'])) {
            $this->updateInvoiceLines($xml, $xpath, $invoiceData['InvoiceLine']);
        }
        
        // FIX 4: Update AllowanceCharges
        
            $this->updateAllowanceCharges($xml, $xpath, $invoiceData['AllowanceCharge']);
        
    }
    

    
    protected function updateBasicFields(\DOMXPath $xpath, array $data): void
    {
        $mappings = [
            'cbc:ID' => 'ID',
            'cbc:IssueDate' => 'IssueDate',
            'cbc:IssueTime' => 'IssueTime',
            'cbc:InvoiceTypeCode' => 'InvoiceTypeCode',
            'cbc:DocumentCurrencyCode' => 'DocumentCurrencyCode',
            'cbc:TaxCurrencyCode' => 'TaxCurrencyCode',
        ];
        
        foreach ($mappings as $xpathExpr => $field) {
            if (isset($data[$field])) {
                $nodeList = $xpath->query("//$xpathExpr");
                if ($nodeList->length > 0) {
                    $node = $nodeList->item(0);
                    foreach ($data[$field] as $item) {
                        if (isset($item['_'])) {
                            $node->nodeValue = $item['_'];
                            if (isset($item['listVersionID'])) {
                                $node->setAttribute('listVersionID', $item['listVersionID']);
                            }
                        }
                    }
                }
            }
        }
        
        // Update AdditionalDocumentReference in UBLExtensions untuk Signature ID
        if (isset($data['ID'][0]['_'])) {
            $nodeList = $xpath->query("//sac:SignatureInformation/cbc:ID");
            if ($nodeList->length > 0) {
                $node = $nodeList->item(0);
                $node->nodeValue = $data['ID'][0]['_'];
            }
        }
        
        // Update InvoicePeriod
        if (isset($data['InvoicePeriod'])) {
            $period = $data['InvoicePeriod'][0];
            $this->updateNode($xpath, "//cac:InvoicePeriod/cbc:StartDate", $period['StartDate'] ?? []);
            $this->updateNode($xpath, "//cac:InvoicePeriod/cbc:EndDate", $period['EndDate'] ?? []);
            $this->updateNode($xpath, "//cac:InvoicePeriod/cbc:Description", $period['Description'] ?? []);
        }
        
        // Update BillingReference
        if (isset($data['BillingReference'])) {
            $ref = $data['BillingReference'][0];
            $this->updateNode($xpath, "//cac:BillingReference/cac:AdditionalDocumentReference/cbc:ID", 
                           $ref['AdditionalDocumentReference'][0]['ID'] ?? []);
        }
    }
    
    protected function updateAccountingParty(\DOMXPath $xpath, string $partyType, array $data): void
    {
        $basePath = "//cac:{$partyType}";
        
        // Update AdditionalAccountID (for supplier only, customer doesn't have it in template)
        if ($partyType === 'AccountingSupplierParty' && isset($data['AdditionalAccountID'])) {
            $this->updateNode($xpath, "$basePath/cbc:AdditionalAccountID", $data['AdditionalAccountID']);
        }
        
        // Update Party section
        if (isset($data['Party'])) {
            $party = $data['Party'][0];
            
            // IndustryClassificationCode (for supplier only)
            if ($partyType === 'AccountingSupplierParty' && isset($party['IndustryClassificationCode'])) {
                $this->updateNode($xpath, "$basePath/cac:Party/cbc:IndustryClassificationCode", 
                               $party['IndustryClassificationCode']);
            }
            
            // PartyIdentification - FIXED untuk handle NRIC dan scheme lainnya
            if (isset($party['PartyIdentification'])) {
                // Different scheme order for supplier vs customer based on template
                $schemeOrder = ($partyType === 'AccountingSupplierParty') 
                    ? ['TIN', 'BRN', 'SST', 'TTX'] 
                    : ['TIN', 'BRN', 'SST', 'TTX']; // Customer template has same order
                
                $schemeToIndex = [];
                foreach ($schemeOrder as $idx => $scheme) {
                    $schemeToIndex[$scheme] = $idx;
                }
                
                // Handle NRIC separately (not in template, might need to add)
                $hasNRIC = false;
                $nricData = null;
                
                foreach ($party['PartyIdentification'] as $id) {
                    if (isset($id['ID'][0]['schemeID'])) {
                        $schemeID = $id['ID'][0]['schemeID'];
                        
                        if ($schemeID === 'NRIC') {
                            $hasNRIC = true;
                            $nricData = $id;
                            continue; // Handle separately
                        }
                        
                        if (isset($schemeToIndex[$schemeID])) {
                            $index = $schemeToIndex[$schemeID] + 1; // XPath is 1-based
                            $this->updateNode($xpath, 
                                "$basePath/cac:Party/cac:PartyIdentification[$index]/cbc:ID",
                                $id['ID']);
                        }
                    }
                }
                
                // If NRIC exists and we're processing customer party, we might need to add it
                // But template doesn't have NRIC slot, so we need to handle this differently
                // For now, we'll replace one of the existing slots with NRIC if needed
                if ($hasNRIC && $nricData && $partyType === 'AccountingCustomerParty') {
                    // Replace SST slot with NRIC if NRIC is present
                    $this->updateNode($xpath, 
                        "$basePath/cac:Party/cac:PartyIdentification[2]/cbc:ID",
                        $nricData['ID']);
                }
            }
            
            // PostalAddress
            if (isset($party['PostalAddress'])) {
                $address = $party['PostalAddress'][0];
                $this->updateNode($xpath, "$basePath/cac:Party/cac:PostalAddress/cbc:CityName", 
                               $address['CityName'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:Party/cac:PostalAddress/cbc:PostalZone", 
                               $address['PostalZone'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:Party/cac:PostalAddress/cbc:CountrySubentityCode", 
                               $address['CountrySubentityCode'] ?? []);
                
                // Address Lines
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($address['AddressLine'][$i-1]['Line'])) {
                        $this->updateNode($xpath, 
                            "$basePath/cac:Party/cac:PostalAddress/cac:AddressLine[$i]/cbc:Line",
                            $address['AddressLine'][$i-1]['Line']);
                    }
                }
                
                // Country
                if (isset($address['Country'][0]['IdentificationCode'])) {
                    $this->updateNode($xpath, 
                        "$basePath/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode",
                        $address['Country'][0]['IdentificationCode']);
                }
            }
            
            // PartyLegalEntity
            if (isset($party['PartyLegalEntity'])) {
                $this->updateNode($xpath, "$basePath/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName", 
                               $party['PartyLegalEntity'][0]['RegistrationName'] ?? []);
            }
            
            // Contact
            if (isset($party['Contact'])) {
                $contact = $party['Contact'][0];
                $this->updateNode($xpath, "$basePath/cac:Party/cac:Contact/cbc:Telephone", 
                               $contact['Telephone'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:Party/cac:Contact/cbc:ElectronicMail", 
                               $contact['ElectronicMail'] ?? []);
            }
        }
    }
    
    protected function updateDelivery(\DOMXPath $xpath, array $data): void
    {
        $basePath = "//cac:Delivery";
        
        if (isset($data['DeliveryParty'])) {
            $party = $data['DeliveryParty'][0];
            
            // PartyLegalEntity
            if (isset($party['PartyLegalEntity'])) {
                $this->updateNode($xpath, 
                    "$basePath/cac:DeliveryParty/cac:PartyLegalEntity/cbc:RegistrationName",
                    $party['PartyLegalEntity'][0]['RegistrationName'] ?? []);
            }
            
            // PartyIdentification - Handle TIN dan BRN (and potentially NRIC)
            $partyIdIndex = 0;
            $foundSchemes = [];
            
            if (isset($party['PartyIdentification'])) {
                foreach ($party['PartyIdentification'] as $id) {
                    if (isset($id['ID'][0]['schemeID'])) {
                        $schemeID = $id['ID'][0]['schemeID'];
                        
                        // Template has positions 1=TIN, 2=BRN
                        if ($schemeID === 'TIN' && !in_array('TIN', $foundSchemes)) {
                            $this->updateNode($xpath, 
                                "$basePath/cac:DeliveryParty/cac:PartyIdentification[1]/cbc:ID",
                                $id['ID']);
                            $foundSchemes[] = 'TIN';
                        } elseif ($schemeID === 'BRN' && !in_array('BRN', $foundSchemes)) {
                            $this->updateNode($xpath, 
                                "$basePath/cac:DeliveryParty/cac:PartyIdentification[2]/cbc:ID",
                                $id['ID']);
                            $foundSchemes[] = 'BRN';
                        } elseif ($schemeID === 'NRIC') {
                            // If NRIC exists, replace BRN slot with NRIC
                            $this->updateNode($xpath, 
                                "$basePath/cac:DeliveryParty/cac:PartyIdentification[2]/cbc:ID",
                                $id['ID']);
                            $foundSchemes[] = 'NRIC';
                        }
                    }
                }
            }
            
            // PostalAddress
            if (isset($party['PostalAddress'])) {
                $address = $party['PostalAddress'][0];
                $this->updateNode($xpath, "$basePath/cac:DeliveryParty/cac:PostalAddress/cbc:CityName", 
                               $address['CityName'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:DeliveryParty/cac:PostalAddress/cbc:PostalZone", 
                               $address['PostalZone'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:DeliveryParty/cac:PostalAddress/cbc:CountrySubentityCode", 
                               $address['CountrySubentityCode'] ?? []);
                
                // Address Lines
                for ($i = 1; $i <= 3; $i++) {
                    if (isset($address['AddressLine'][$i-1]['Line'])) {
                        $this->updateNode($xpath, 
                            "$basePath/cac:DeliveryParty/cac:PostalAddress/cac:AddressLine[$i]/cbc:Line",
                            $address['AddressLine'][$i-1]['Line']);
                    }
                }
                
                // Country
                if (isset($address['Country'][0]['IdentificationCode'])) {
                    $this->updateNode($xpath, 
                        "$basePath/cac:DeliveryParty/cac:PostalAddress/cac:Country/cbc:IdentificationCode",
                        $address['Country'][0]['IdentificationCode']);
                }
            }
        }
        
        // Shipment
        if (isset($data['Shipment'])) {
            $shipment = $data['Shipment'][0];
            $this->updateNode($xpath, "$basePath/cac:Shipment/cbc:ID", $shipment['ID'] ?? []);
            
            if (isset($shipment['FreightAllowanceCharge'])) {
                $charge = $shipment['FreightAllowanceCharge'][0];
                $this->updateNode($xpath, "$basePath/cac:Shipment/cac:FreightAllowanceCharge/cbc:ChargeIndicator", 
                               $charge['ChargeIndicator'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:Shipment/cac:FreightAllowanceCharge/cbc:AllowanceChargeReason", 
                               $charge['AllowanceChargeReason'] ?? []);
                $this->updateNode($xpath, "$basePath/cac:Shipment/cac:FreightAllowanceCharge/cbc:Amount", 
                               $charge['Amount'] ?? []);
            }
        }
    }
    
    protected function updatePaymentMeans(\DOMXPath $xpath, array $data): void
    {
        $basePath = "//cac:PaymentMeans";
        
        $this->updateNode($xpath, "$basePath/cbc:PaymentMeansCode", $data['PaymentMeansCode'] ?? []);
        
        // Fix PaymentMeansCode value - convert "Cash" to "01" if needed
        $nodeList = $xpath->query("$basePath/cbc:PaymentMeansCode");
        if ($nodeList->length > 0) {
            $node = $nodeList->item(0);
            if ($node->nodeValue === 'Cash') {
                $node->nodeValue = '01';
            }
        }
        
        if (isset($data['PayeeFinancialAccount'][0]['ID'])) {
            $this->updateNode($xpath, "$basePath/cac:PayeeFinancialAccount/cbc:ID", 
                           $data['PayeeFinancialAccount'][0]['ID']);
        }
    }
    
   

    protected function updateTaxTotal(\DOMXPath $xpath, array $data): void
    {
        $basePath = "//cac:TaxTotal";
        
        // Update TaxAmount
        $this->updateNode($xpath, "$basePath/cbc:TaxAmount", $data['TaxAmount'] ?? []);
        
        if (isset($data['TaxSubtotal'][0])) {
            $subtotal = $data['TaxSubtotal'][0];
            $subPath = "$basePath/cac:TaxSubtotal";
            
            // Update TaxableAmount dan TaxAmount
            $this->updateNode($xpath, "$subPath/cbc:TaxableAmount", $subtotal['TaxableAmount'] ?? []);
            $this->updateNode($xpath, "$subPath/cbc:TaxAmount", $subtotal['TaxAmount'] ?? []);
            
            // **FIX 1: PASTIKAN Percent ADA di TaxSubtotal**
            $this->ensureTaxSubtotalPercent($xpath, $subPath, $subtotal);
            
            if (isset($subtotal['TaxCategory'][0])) {
                $category = $subtotal['TaxCategory'][0];
                $catPath = "$subPath/cac:TaxCategory";
                
                // Update ID
                if (isset($category['ID'])) {
                    $this->updateNode($xpath, "$catPath/cbc:ID", $category['ID']);
                } else {
                    $this->updateNode($xpath, "$catPath/cbc:ID", [['_' => '01']]);
                }
                
                // **FIX 2: PASTIKAN Percent ADA di TaxCategory**
                $this->ensureTaxCategoryPercent($xpath, $catPath, $category);
                
                // **FIX 3: PASTIKAN TaxExemptionReason ADA di TaxCategory**
                $this->ensureTaxExemptionReason($xpath, $catPath, $category);
                
                // Update TaxScheme
                if (isset($category['TaxScheme'][0]['ID'])) {
                    $this->updateNode($xpath, "$catPath/cac:TaxScheme/cbc:ID", 
                                   $category['TaxScheme'][0]['ID']);
                }
            }
        }
    }
    
    /**
     * Pastikan Percent ada di TaxSubtotal
     */
    protected function ensureTaxSubtotalPercent(\DOMXPath $xpath, string $subPath, array $subtotal): void
    {
        $percentNodeList = $xpath->query("$subPath/cbc:Percent");
        
        if ($percentNodeList->length === 0) {
            // Node Percent tidak ada, BUAT BARU
            $parentNode = $xpath->query($subPath)->item(0);
            if ($parentNode) {
                $doc = $parentNode->ownerDocument;
                
                // Tentukan nilai Percent
                $percentValue = '6.00'; // Default
                if (isset($subtotal['Percent'][0]['_'])) {
                    $percentValue = $subtotal['Percent'][0]['_'];
                } elseif (isset($subtotal['TaxCategory'][0]['Percent'][0]['_'])) {
                    $percentValue = $subtotal['TaxCategory'][0]['Percent'][0]['_'];
                }
                
                $percentNode = $doc->createElement('cbc:Percent', $percentValue);
                
                // Cari TaxAmount node
                $taxAmountNode = $xpath->query("$subPath/cbc:TaxAmount")->item(0);
                
                if ($taxAmountNode) {
                    // Sisipkan setelah TaxAmount
                    if ($taxAmountNode->nextSibling) {
                        $parentNode->insertBefore($percentNode, $taxAmountNode->nextSibling);
                    } else {
                        $parentNode->appendChild($percentNode);
                    }
                } else {
                    // Jika tidak ada TaxAmount, tambahkan di akhir
                    $parentNode->appendChild($percentNode);
                }
            }
        } else {
            // Node Percent sudah ada, UPDATE nilainya
            $node = $percentNodeList->item(0);
            if (isset($subtotal['Percent'][0]['_'])) {
                $node->nodeValue = $subtotal['Percent'][0]['_'];
            } elseif (isset($subtotal['TaxCategory'][0]['Percent'][0]['_'])) {
                $node->nodeValue = $subtotal['TaxCategory'][0]['Percent'][0]['_'];
            }
        }
    }
    
    /**
     * Pastikan Percent ada di TaxCategory
     */
    protected function ensureTaxCategoryPercent(\DOMXPath $xpath, string $catPath, array $category): void
    {
        $percentNodeList = $xpath->query("$catPath/cbc:Percent");
        
        if ($percentNodeList->length === 0) {
            // Node Percent tidak ada, BUAT BARU
            $parentNode = $xpath->query($catPath)->item(0);
            if ($parentNode) {
                $doc = $parentNode->ownerDocument;
                
                // Tentukan nilai Percent
                $percentValue = '6.00'; // Default
                if (isset($category['Percent'][0]['_'])) {
                    $percentValue = $category['Percent'][0]['_'];
                }
                
                $percentNode = $doc->createElement('cbc:Percent', $percentValue);
                
                // Cari ID node
                $idNode = $xpath->query("$catPath/cbc:ID")->item(0);
                
                if ($idNode) {
                    // Sisipkan setelah ID
                    if ($idNode->nextSibling) {
                        $parentNode->insertBefore($percentNode, $idNode->nextSibling);
                    } else {
                        $parentNode->appendChild($percentNode);
                    }
                } else {
                    // Jika tidak ada ID, tambahkan di awal
                    $parentNode->insertBefore($percentNode, $parentNode->firstChild);
                }
            }
        } else {
            // Node Percent sudah ada, UPDATE nilainya
            $node = $percentNodeList->item(0);
            if (isset($category['Percent'][0]['_'])) {
                $node->nodeValue = $category['Percent'][0]['_'];
            }
        }
    }
    
    /**
     * Pastikan TaxExemptionReason ada di TaxCategory
     */
    protected function ensureTaxExemptionReason(\DOMXPath $xpath, string $catPath, array $category): void
    {
        $reasonNodeList = $xpath->query("$catPath/cbc:TaxExemptionReason");
        
        if ($reasonNodeList->length === 0) {
            // Node TaxExemptionReason tidak ada, BUAT BARU
            $parentNode = $xpath->query($catPath)->item(0);
            if ($parentNode) {
                $doc = $parentNode->ownerDocument;
                
                // Tentukan nilai TaxExemptionReason
                $reasonValue = ''; // Default kosong
                if (isset($category['TaxExemptionReason'][0]['_'])) {
                    $reasonValue = $category['TaxExemptionReason'][0]['_'];
                }
                
                $reasonNode = $doc->createElement('cbc:TaxExemptionReason', $reasonValue);
                
                // Cari Percent node terlebih dahulu
                $percentNode = $xpath->query("$catPath/cbc:Percent")->item(0);
                
                if ($percentNode) {
                    // Sisipkan setelah Percent
                    if ($percentNode->nextSibling) {
                        $parentNode->insertBefore($reasonNode, $percentNode->nextSibling);
                    } else {
                        $parentNode->appendChild($reasonNode);
                    }
                } else {
                    // Jika tidak ada Percent, cari ID node
                    $idNode = $xpath->query("$catPath/cbc:ID")->item(0);
                    if ($idNode) {
                        // Sisipkan setelah ID
                        if ($idNode->nextSibling) {
                            $parentNode->insertBefore($reasonNode, $idNode->nextSibling);
                        } else {
                            $parentNode->appendChild($reasonNode);
                        }
                    } else {
                        // Jika tidak ada ID, tambahkan di akhir
                        $parentNode->appendChild($reasonNode);
                    }
                }
            }
        } else {
            // Node TaxExemptionReason sudah ada, UPDATE nilainya
            $node = $reasonNodeList->item(0);
            if (isset($category['TaxExemptionReason'][0]['_'])) {
                $node->nodeValue = $category['TaxExemptionReason'][0]['_'];
            }
        }
    }

    protected function updateAllowanceCharges(\DOMDocument $xml, \DOMXPath $xpath, array $allowanceCharges): void
{
    // Hapus semua AllowanceCharge yang ada di root level
    $existingCharges = $xpath->query("//cac:AllowanceCharge[not(parent::cac:InvoiceLine)]");
    foreach ($existingCharges as $charge) {
        $charge->parentNode->removeChild($charge);
    }
    
    // Temukan node setelah PrepaidPayment untuk menambahkan AllowanceCharge
    $prepaidPayment = $xpath->query("//cac:PrepaidPayment");
    $insertAfterNode = null;
    
    if ($prepaidPayment->length > 0) {
        $insertAfterNode = $prepaidPayment->item(0);
    } else {
        // Cari node TaxTotal sebagai fallback
        $taxTotal = $xpath->query("//cac:TaxTotal");
        if ($taxTotal->length > 0) {
            $insertAfterNode = $taxTotal->item(0);
        }
    }
    
    if ($insertAfterNode && $insertAfterNode->parentNode) {
        $parentNode = $insertAfterNode->parentNode;
        
        foreach ($allowanceCharges as $chargeData) {
            $charge = $this->createAllowanceCharge($xml, $chargeData);
            if ($charge) {
                $parentNode->insertBefore($charge, $insertAfterNode->nextSibling);
                $insertAfterNode = $charge; // Update untuk insert berikutnya
            }
        }
    }
}

// ... kode sebelumnya ...

protected function createAllowanceCharge(\DOMDocument $xml, array $chargeData): ?\DOMElement
{
    if (empty($chargeData)) {
        return null;
    }
    
    $charge = $xml->createElement('cac:AllowanceCharge');
    
    // ChargeIndicator
    if (isset($chargeData['ChargeIndicator'][0]['_'])) {
        $indicator = $xml->createElement('cbc:ChargeIndicator', 
            $chargeData['ChargeIndicator'][0]['_'] ? 'true' : 'false');
        $charge->appendChild($indicator);
    } else {
        $indicator = $xml->createElement('cbc:ChargeIndicator', 'false');
        $charge->appendChild($indicator);
    }
    
    // AllowanceChargeReason
    if (isset($chargeData['AllowanceChargeReason'][0]['_'])) {
        $reason = $xml->createElement('cbc:AllowanceChargeReason', 
            $chargeData['AllowanceChargeReason'][0]['_']);
        $charge->appendChild($reason);
    }
    
    // MultiplierFactorNumeric
    if (isset($chargeData['MultiplierFactorNumeric'][0]['_'])) {
        $multiplier = $xml->createElement('cbc:MultiplierFactorNumeric', 
            $chargeData['MultiplierFactorNumeric'][0]['_']);
        $charge->appendChild($multiplier);
    }
    
    // Amount
    if (isset($chargeData['Amount'][0]['_'])) {
        $amount = $xml->createElement('cbc:Amount', $chargeData['Amount'][0]['_']);
        if (isset($chargeData['Amount'][0]['currencyID'])) {
            $amount->setAttribute('currencyID', $chargeData['Amount'][0]['currencyID']);
        } else {
            $amount->setAttribute('currencyID', 'MYR');
        }
        $charge->appendChild($amount);
    }
    
    return $charge;
}

protected function updateNode(\DOMXPath $xpath, string $path, array $data): void
{
    $nodeList = $xpath->query($path);
    if ($nodeList->length > 0 && isset($data[0]['_'])) {
        $node = $nodeList->item(0);
        $node->nodeValue = $data[0]['_'];
        
        // Update attributes if present
        if (isset($data[0]['currencyID'])) {
            $node->setAttribute('currencyID', $data[0]['currencyID']);
        }
        if (isset($data[0]['schemeID'])) {
            $node->setAttribute('schemeID', $data[0]['schemeID']);
        }
        if (isset($data[0]['schemeAgencyID'])) {
            $node->setAttribute('schemeAgencyID', $data[0]['schemeAgencyID']);
        }
        if (isset($data[0]['schemeAgencyName'])) {
            $node->setAttribute('schemeAgencyName', $data[0]['schemeAgencyName']);
        }
        if (isset($data[0]['listID'])) {
            $node->setAttribute('listID', $data[0]['listID']);
        }
        if (isset($data[0]['listAgencyID'])) {
            $node->setAttribute('listAgencyID', $data[0]['listAgencyID']);
        }
        if (isset($data[0]['listVersionID'])) {
            $node->setAttribute('listVersionID', $data[0]['listVersionID']);
        }
    }
}
    
    protected function updateLegalMonetaryTotal(\DOMXPath $xpath, array $data): void
    {
        $basePath = "//cac:LegalMonetaryTotal";
        
        $fields = [
            'LineExtensionAmount' => 'LineExtensionAmount',
            'TaxExclusiveAmount' => 'TaxExclusiveAmount',
            'TaxInclusiveAmount' => 'TaxInclusiveAmount',
            'AllowanceTotalAmount' => 'AllowanceTotalAmount',
            'ChargeTotalAmount' => 'ChargeTotalAmount',
            'PayableRoundingAmount' => 'PayableRoundingAmount',
            'PayableAmount' => 'PayableAmount',
        ];
        
        foreach ($fields as $field => $jsonKey) {
            if (isset($data[$jsonKey])) {
                $this->updateNode($xpath, "$basePath/cbc:$field", $data[$jsonKey]);
            }
        }
    }
    
    protected function updateInvoiceLines(\DOMDocument $xml, \DOMXPath $xpath, array $invoiceLines): void
    {
        // First, remove ALL existing InvoiceLine elements from template
        $existingLines = $xpath->query("//cac:InvoiceLine");
        foreach ($existingLines as $line) {
            $line->parentNode->removeChild($line);
        }
        
        // Get the parent element where InvoiceLines should be added
        $legalMonetaryTotal = $xpath->query("//cac:LegalMonetaryTotal");
        if ($legalMonetaryTotal->length > 0) {
            $parentNode = $legalMonetaryTotal->item(0)->parentNode;
            
            // **FIX: Filter untuk menghapus duplikat InvoiceLine**
            $uniqueItems = $this->getUniqueInvoiceLines($invoiceLines);
            
            // **FIX: Buat InvoiceLine untuk setiap item yang unik**
            foreach ($uniqueItems as $lineData) {
                $this->createInvoiceLine($xml, $parentNode, $lineData);
            }
            
            // Jika tidak ada items, buat satu default
            if (empty($uniqueItems) && !empty($invoiceLines)) {
                $this->createInvoiceLine($xml, $parentNode, $invoiceLines[0]);
            }
        }
    }
    
    /**
     * Filter untuk mendapatkan InvoiceLine yang unik (hilangkan duplikat)
     * Perbandingan berdasarkan ID, Description, dan PriceAmount
     */
    protected function getUniqueInvoiceLines(array $invoiceLines): array
    {
        $uniqueLines = [];
        $seenKeys = [];
        
        foreach ($invoiceLines as $line) {
            // Buat unique key berdasarkan ID, Description, dan PriceAmount
            $id = $line['ID'][0]['_'] ?? '';
            $desc = $line['Item'][0]['Description'][0]['_'] ?? '';
            $price = $line['Price'][0]['PriceAmount'][0]['_'] ?? '0';
            $key = $id . '_' . $desc . '_' . $price;
            
            if (!in_array($key, $seenKeys)) {
                $uniqueLines[] = $line;
                $seenKeys[] = $key;
            }
        }
        
        return $uniqueLines;
    }
    
    protected function createInvoiceLine(\DOMDocument $xml, \DOMNode $parent, array $lineData): void
    {
        // Create InvoiceLine element
        $invoiceLine = $xml->createElement('cac:InvoiceLine');
        
        // Add ID - Gunakan dari data
        if (isset($lineData['ID'][0]['_'])) {
            $id = $xml->createElement('cbc:ID', $lineData['ID'][0]['_']);
            $invoiceLine->appendChild($id);
        } else {
            // Generate ID jika tidak ada
            static $lineCounter = 1;
            $id = $xml->createElement('cbc:ID', $lineCounter++);
            $invoiceLine->appendChild($id);
        }
        
        // Add InvoicedQuantity - Gunakan dari data
        if (isset($lineData['InvoicedQuantity'][0]['_'])) {
            $quantity = $xml->createElement('cbc:InvoicedQuantity', $lineData['InvoicedQuantity'][0]['_']);
            $unitCode = $lineData['InvoicedQuantity'][0]['unitCode'] ?? 'C62';
            $quantity->setAttribute('unitCode', $unitCode);
            $invoiceLine->appendChild($quantity);
        } else {
            // Default quantity jika tidak ada
            $quantity = $xml->createElement('cbc:InvoicedQuantity', '1');
            $quantity->setAttribute('unitCode', 'C62');
            $invoiceLine->appendChild($quantity);
        }
        
        // Add LineExtensionAmount
        if (isset($lineData['LineExtensionAmount'][0]['_'])) {
            $amount = $xml->createElement('cbc:LineExtensionAmount', $lineData['LineExtensionAmount'][0]['_']);
            $currencyID = $lineData['LineExtensionAmount'][0]['currencyID'] ?? 'MYR';
            $amount->setAttribute('currencyID', $currencyID);
            $invoiceLine->appendChild($amount);
        } else {
            // Calculate LineExtensionAmount jika tidak ada
            $quantity = $lineData['InvoicedQuantity'][0]['_'] ?? 1;
            $price = $lineData['Price'][0]['PriceAmount'][0]['_'] ?? 0;
            $lineExtension = $quantity * $price;
            
            $amount = $xml->createElement('cbc:LineExtensionAmount', $lineExtension);
            $amount->setAttribute('currencyID', 'MYR');
            $invoiceLine->appendChild($amount);
        }
        
        // FIX 4: Tambahkan AllowanceCharge berdasarkan data asli
        if (isset($lineData['AllowanceCharge']) && is_array($lineData['AllowanceCharge'])) {
            foreach ($lineData['AllowanceCharge'] as $chargeData) {
                $charge = $xml->createElement('cac:AllowanceCharge');
                
                if (isset($chargeData['ChargeIndicator'][0]['_'])) {
                    $indicator = $xml->createElement('cbc:ChargeIndicator', 
                        $chargeData['ChargeIndicator'][0]['_'] ? 'true' : 'false');
                    $charge->appendChild($indicator);
                } else {
                    // Default jika tidak ada
                    $indicator = $xml->createElement('cbc:ChargeIndicator', 'false');
                    $charge->appendChild($indicator);
                }
                
                if (isset($chargeData['AllowanceChargeReason'][0]['_'])) {
                    $reason = $xml->createElement('cbc:AllowanceChargeReason', 
                        $chargeData['AllowanceChargeReason'][0]['_']);
                    $charge->appendChild($reason);
                }
                
                if (isset($chargeData['MultiplierFactorNumeric'][0]['_'])) {
                    $multiplier = $xml->createElement('cbc:MultiplierFactorNumeric', 
                        $chargeData['MultiplierFactorNumeric'][0]['_']);
                    $charge->appendChild($multiplier);
                }
                
                if (isset($chargeData['Amount'][0]['_'])) {
                    $amount = $xml->createElement('cbc:Amount', $chargeData['Amount'][0]['_']);
                    if (isset($chargeData['Amount'][0]['currencyID'])) {
                        $amount->setAttribute('currencyID', $chargeData['Amount'][0]['currencyID']);
                    } else {
                        $amount->setAttribute('currencyID', 'MYR');
                    }
                    $charge->appendChild($amount);
                }
                
                $invoiceLine->appendChild($charge);
            }
        }
        
        // Add TaxTotal
        if (isset($lineData['TaxTotal'][0])) {
            $taxTotal = $this->createTaxTotal($xml, $lineData['TaxTotal'][0]);
            $invoiceLine->appendChild($taxTotal);
        } else {
            // Create default TaxTotal jika tidak ada
            $taxTotal = $this->createDefaultTaxTotal($xml);
            $invoiceLine->appendChild($taxTotal);
        }
        
        // Create Item dengan semua field yang diperlukan
        if (isset($lineData['Item'][0])) {
            $item = $this->createItem($xml, $lineData['Item'][0]);
            $invoiceLine->appendChild($item);
        } else {
            // Create minimal item jika tidak ada data
            $item = $xml->createElement('cac:Item');
            $desc = $xml->createElement('cbc:Description', 'Item');
            $item->appendChild($desc);
            $invoiceLine->appendChild($item);
        }
        
        // Add Price
        if (isset($lineData['Price'][0])) {
            $price = $xml->createElement('cac:Price');
            if (isset($lineData['Price'][0]['PriceAmount'][0]['_'])) {
                $priceAmount = $xml->createElement('cbc:PriceAmount', 
                    $lineData['Price'][0]['PriceAmount'][0]['_']);
                if (isset($lineData['Price'][0]['PriceAmount'][0]['currencyID'])) {
                    $priceAmount->setAttribute('currencyID', 
                        $lineData['Price'][0]['PriceAmount'][0]['currencyID']);
                } else {
                    $priceAmount->setAttribute('currencyID', 'MYR');
                }
                $price->appendChild($priceAmount);
            }
            $invoiceLine->appendChild($price);
        }
        
        // Add ItemPriceExtension
        if (isset($lineData['ItemPriceExtension'][0])) {
            $extension = $xml->createElement('cac:ItemPriceExtension');
            if (isset($lineData['ItemPriceExtension'][0]['Amount'][0]['_'])) {
                $amount = $xml->createElement('cbc:Amount', 
                    $lineData['ItemPriceExtension'][0]['Amount'][0]['_']);
                if (isset($lineData['ItemPriceExtension'][0]['Amount'][0]['currencyID'])) {
                    $amount->setAttribute('currencyID', 
                        $lineData['ItemPriceExtension'][0]['Amount'][0]['currencyID']);
                } else {
                    $amount->setAttribute('currencyID', 'MYR');
                }
                $extension->appendChild($amount);
            }
            $invoiceLine->appendChild($extension);
        }
        
        // Add the InvoiceLine to parent
        $parent->appendChild($invoiceLine);
    }
    
    protected function createItem(\DOMDocument $xml, array $itemData): \DOMElement
    {
        $item = $xml->createElement('cac:Item');
        
        // Description
        if (isset($itemData['Description'][0]['_'])) {
            $desc = $xml->createElement('cbc:Description', $itemData['Description'][0]['_']);
            $item->appendChild($desc);
        }
        
        // FIX 5: Tambahkan OriginCountry jika ada dalam data
        if (isset($itemData['OriginCountry'])) {
            $originCountry = $xml->createElement('cac:OriginCountry');
            $originCode = $xml->createElement('cbc:IdentificationCode', 
                $itemData['OriginCountry'][0]['IdentificationCode'][0]['_'] ?? 'MYS');
            $originCountry->appendChild($originCode);
            $item->appendChild($originCountry);
        }
        
        // FIX 6: CommodityClassification dengan kode yang benar sesuai original
        // Original: "9800.00.0010" untuk PTC dan "003" untuk CLASS
        if (isset($itemData['CommodityClassification']) && is_array($itemData['CommodityClassification'])) {
            // **PERBAIKAN: Hanya buat 2 CommodityClassification untuk PTC dan CLASS**
            $classifications = [
                'PTC' => null,
                'CLASS' => null
            ];
            
            // Cari classification berdasarkan listID
            foreach ($itemData['CommodityClassification'] as $classification) {
                if (isset($classification['ItemClassificationCode'][0]['_'])) {
                    $listID = $classification['ItemClassificationCode'][0]['listID'] ?? '';
                    $codeValue = $classification['ItemClassificationCode'][0]['_'];
                    
                    if ($listID === 'PTC' || $listID === 'CLASS') {
                        $classifications[$listID] = [
                            'code' => $codeValue,
                            'listID' => $listID,
                            'attributes' => $classification['ItemClassificationCode'][0]
                        ];
                    }
                }
            }
            
            // **PERBAIKAN: Gunakan kode default jika tidak ada**
            // Jika PTC tidak ada atau kode "12344321", gunakan "9800.00.0010"
            if ($classifications['PTC'] === null || $classifications['PTC']['code'] === '12344321') {
                $classifications['PTC'] = [
                    'code' => '9800.00.0010',
                    'listID' => 'PTC',
                    'attributes' => ['listAgencyID' => 'MY', 'listVersionID' => '1.0']
                ];
            }
            
            // Jika CLASS tidak ada atau kode "12344321", gunakan "003"
            if ($classifications['CLASS'] === null || $classifications['CLASS']['code'] === '12344321') {
                $classifications['CLASS'] = [
                    'code' => '003',
                    'listID' => 'CLASS',
                    'attributes' => ['listAgencyID' => '6']
                ];
            }
            
            // Buat CommodityClassification untuk PTC dan CLASS
            foreach ($classifications as $listID => $classification) {
                if ($classification !== null) {
                    $class = $xml->createElement('cac:CommodityClassification');
                    $code = $xml->createElement('cac:ItemClassificationCode', $classification['code']);
                    
                    $code->setAttribute('listID', $listID);
                    
                    if (isset($classification['attributes']['listAgencyID'])) {
                        $code->setAttribute('listAgencyID', $classification['attributes']['listAgencyID']);
                    } else {
                        // Default attributes
                        if ($listID === 'PTC') {
                            $code->setAttribute('listAgencyID', 'MY');
                            $code->setAttribute('listVersionID', '1.0');
                        } elseif ($listID === 'CLASS') {
                            $code->setAttribute('listAgencyID', '6');
                        }
                    }
                    
                    if (isset($classification['attributes']['listVersionID'])) {
                        $code->setAttribute('listVersionID', $classification['attributes']['listVersionID']);
                    }
                    
                    $class->appendChild($code);
                    $item->appendChild($class);
                }
            }
        } else {
            // **PERBAIKAN: Jika tidak ada CommodityClassification, buat default**
            // Default: PTC dengan "9800.00.0010" dan CLASS dengan "003"
            
            // PTC
            $classPTC = $xml->createElement('cac:CommodityClassification');
            $codePTC = $xml->createElement('cac:ItemClassificationCode', '9800.00.0010');
            $codePTC->setAttribute('listID', 'PTC');
            $codePTC->setAttribute('listAgencyID', 'MY');
            $codePTC->setAttribute('listVersionID', '1.0');
            $classPTC->appendChild($codePTC);
            $item->appendChild($classPTC);
            
            // CLASS
            $classCLASS = $xml->createElement('cac:CommodityClassification');
            $codeCLASS = $xml->createElement('cac:ItemClassificationCode', '003');
            $codeCLASS->setAttribute('listID', 'CLASS');
            $codeCLASS->setAttribute('listAgencyID', '6');
            $classCLASS->appendChild($codeCLASS);
            $item->appendChild($classCLASS);
        }
        
        return $item;
    }
    
    /**
     * Create default TaxTotal structure
     */
    protected function createDefaultTaxTotal(\DOMDocument $xml): \DOMElement
    {
        $taxTotal = $xml->createElement('cac:TaxTotal');
        
        $amount = $xml->createElement('cbc:TaxAmount', '0');
        $amount->setAttribute('currencyID', 'MYR');
        $taxTotal->appendChild($amount);
        
        $subtotal = $xml->createElement('cac:TaxSubtotal');
        
        $taxable = $xml->createElement('cbc:TaxableAmount', '0');
        $taxable->setAttribute('currencyID', 'MYR');
        $subtotal->appendChild($taxable);
        
        $taxAmount = $xml->createElement('cbc:TaxAmount', '0');
        $taxAmount->setAttribute('currencyID', 'MYR');
        $subtotal->appendChild($taxAmount);
        
        // FIX 2: Tambahkan Percent di TaxSubtotal
        $percent = $xml->createElement('cbc:Percent', '0');
        $subtotal->appendChild($percent);
        
        $category = $xml->createElement('cac:TaxCategory');
        
        // FIX 3: ID harus "01" bukan "E"
        $id = $xml->createElement('cbc:ID', '01');
        $category->appendChild($id);
        
        $taxPercent = $xml->createElement('cbc:Percent', '0');
        $category->appendChild($taxPercent);
        
        $reason = $xml->createElement('cbc:TaxExemptionReason', 'SST');
        $category->appendChild($reason);
        
        $scheme = $xml->createElement('cac:TaxScheme');
        $schemeId = $xml->createElement('cbc:ID', 'OTH');
        $schemeId->setAttribute('schemeID', 'UN/ECE 5153');
        $schemeId->setAttribute('schemeAgencyID', '6');
        $scheme->appendChild($schemeId);
        $category->appendChild($scheme);
        
        $subtotal->appendChild($category);
        $taxTotal->appendChild($subtotal);
        
        return $taxTotal;
    }
    
    protected function createTaxTotal(\DOMDocument $xml, array $taxData): \DOMElement
    {
        $taxTotal = $xml->createElement('cac:TaxTotal');
        
        if (isset($taxData['TaxAmount'][0]['_'])) {
            $amount = $xml->createElement('cbc:TaxAmount', $taxData['TaxAmount'][0]['_']);
            if (isset($taxData['TaxAmount'][0]['currencyID'])) {
                $amount->setAttribute('currencyID', $taxData['TaxAmount'][0]['currencyID']);
            } else {
                $amount->setAttribute('currencyID', 'MYR');
            }
            $taxTotal->appendChild($amount);
        }
        
        if (isset($taxData['TaxSubtotal'][0])) {
            $subtotal = $xml->createElement('cac:TaxSubtotal');
            $subData = $taxData['TaxSubtotal'][0];
            
            // TaxableAmount
            if (isset($subData['TaxableAmount'][0]['_'])) {
                $taxable = $xml->createElement('cbc:TaxableAmount', $subData['TaxableAmount'][0]['_']);
                if (isset($subData['TaxableAmount'][0]['currencyID'])) {
                    $taxable->setAttribute('currencyID', $subData['TaxableAmount'][0]['currencyID']);
                } else {
                    $taxable->setAttribute('currencyID', 'MYR');
                }
                $subtotal->appendChild($taxable);
            }
            
            // TaxAmount
            if (isset($subData['TaxAmount'][0]['_'])) {
                $taxAmount = $xml->createElement('cbc:TaxAmount', $subData['TaxAmount'][0]['_']);
                if (isset($subData['TaxAmount'][0]['currencyID'])) {
                    $taxAmount->setAttribute('currencyID', $subData['TaxAmount'][0]['currencyID']);
                } else {
                    $taxAmount->setAttribute('currencyID', 'MYR');
                }
                $subtotal->appendChild($taxAmount);
            }
            
            // FIX 2: Percent harus ada di TaxSubtotal
            if (isset($subData['Percent'][0]['_'])) {
                $percent = $xml->createElement('cbc:Percent', $subData['Percent'][0]['_']);
                $subtotal->appendChild($percent);
            } elseif (isset($subData['TaxCategory'][0]['Percent'][0]['_'])) {
                $percent = $xml->createElement('cbc:Percent', $subData['TaxCategory'][0]['Percent'][0]['_']);
                $subtotal->appendChild($percent);
            } else {
                $percent = $xml->createElement('cbc:Percent', '0');
                $subtotal->appendChild($percent);
            }
            
            // TaxCategory
            if (isset($subData['TaxCategory'][0])) {
                $category = $xml->createElement('cac:TaxCategory');
                $catData = $subData['TaxCategory'][0];
                
                // FIX 3: ID harus "01" bukan "E"
                if (isset($catData['ID'][0]['_'])) {
                    $idValue = $catData['ID'][0]['_'];
                    // Jika ID adalah "E", ubah ke "01"
                    if ($idValue === 'E') {
                        $idValue = '01';
                    }
                    $id = $xml->createElement('cbc:ID', $idValue);
                    $category->appendChild($id);
                } else {
                    $id = $xml->createElement('cbc:ID', '01');
                    $category->appendChild($id);
                }
                
                // Percent
                if (isset($catData['Percent'][0]['_'])) {
                    $percent = $xml->createElement('cbc:Percent', $catData['Percent'][0]['_']);
                    $category->appendChild($percent);
                } else {
                    $percent = $xml->createElement('cbc:Percent', '0');
                    $category->appendChild($percent);
                }
                
                // TaxExemptionReason
                if (isset($catData['TaxExemptionReason'][0]['_'])) {
                    $reason = $xml->createElement('cbc:TaxExemptionReason', 
                        $catData['TaxExemptionReason'][0]['_']);
                    $category->appendChild($reason);
                } else {
                    $reason = $xml->createElement('cbc:TaxExemptionReason', 'SST');
                    $category->appendChild($reason);
                }
                
                // TaxScheme
                if (isset($catData['TaxScheme'][0])) {
                    $scheme = $xml->createElement('cac:TaxScheme');
                    if (isset($catData['TaxScheme'][0]['ID'][0]['_'])) {
                        $schemeId = $xml->createElement('cbc:ID', $catData['TaxScheme'][0]['ID'][0]['_']);
                        if (isset($catData['TaxScheme'][0]['ID'][0]['schemeID'])) {
                            $schemeId->setAttribute('schemeID', 
                                $catData['TaxScheme'][0]['ID'][0]['schemeID']);
                        }
                        if (isset($catData['TaxScheme'][0]['ID'][0]['schemeAgencyID'])) {
                            $schemeId->setAttribute('schemeAgencyID', 
                                $catData['TaxScheme'][0]['ID'][0]['schemeAgencyID']);
                        }
                        $scheme->appendChild($schemeId);
                    }
                    $category->appendChild($scheme);
                } else {
                    $scheme = $xml->createElement('cac:TaxScheme');
                    $schemeId = $xml->createElement('cbc:ID', 'OTH');
                    $schemeId->setAttribute('schemeID', 'UN/ECE 5153');
                    $schemeId->setAttribute('schemeAgencyID', '6');
                    $scheme->appendChild($schemeId);
                    $category->appendChild($scheme);
                }
                
                $subtotal->appendChild($category);
            }
            
            $taxTotal->appendChild($subtotal);
        }
        
        return $taxTotal;
    }
    
   
}