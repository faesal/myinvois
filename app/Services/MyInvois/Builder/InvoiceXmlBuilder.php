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
    
    protected function updateXmlFromJson(\DOMDocument $xml, array $invoiceData): void
    {
        // Create XPath for easier navigation
        $xpath = new \DOMXPath($xml);
        
        // Register namespaces
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        
        // Update basic fields
        $this->updateBasicFields($xpath, $invoiceData);
        
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
        if (isset($invoiceData['TaxTotal'])) {
            $this->updateTaxTotal($xpath, $invoiceData['TaxTotal'][0]);
        }
        
        // Update LegalMonetaryTotal
        if (isset($invoiceData['LegalMonetaryTotal'])) {
            $this->updateLegalMonetaryTotal($xpath, $invoiceData['LegalMonetaryTotal'][0]);
        }
        
        // Update InvoiceLine items
        if (isset($invoiceData['InvoiceLine'])) {
            $this->updateInvoiceLines($xml, $xpath, $invoiceData['InvoiceLine']);
        }
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
        
        // Update AdditionalAccountID
        if (isset($data['AdditionalAccountID'])) {
            $this->updateNode($xpath, "$basePath/cbc:AdditionalAccountID", $data['AdditionalAccountID']);
        }
        
        // Update Party section
        if (isset($data['Party'])) {
            $party = $data['Party'][0];
            
            // IndustryClassificationCode
            if (isset($party['IndustryClassificationCode'])) {
                $this->updateNode($xpath, "$basePath/cac:Party/cbc:IndustryClassificationCode", 
                               $party['IndustryClassificationCode']);
            }
            
            // PartyIdentification
            $partyIdIndex = 0;
            foreach (['TIN', 'BRN', 'SST', 'TTX'] as $scheme) {
                if (isset($party['PartyIdentification'])) {
                    foreach ($party['PartyIdentification'] as $id) {
                        if (isset($id['ID'][0]['schemeID']) && $id['ID'][0]['schemeID'] === $scheme) {
                            $index = $partyIdIndex;
                            $this->updateNode($xpath, 
                                "$basePath/cac:Party/cac:PartyIdentification[$index]/cbc:ID",
                                $id['ID']);
                            $partyIdIndex++;
                        }
                    }
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
            
            // PartyIdentification
            $partyIdIndex = 0;
            foreach (['TIN', 'BRN'] as $scheme) {
                if (isset($party['PartyIdentification'])) {
                    foreach ($party['PartyIdentification'] as $id) {
                        if (isset($id['ID'][0]['schemeID']) && $id['ID'][0]['schemeID'] === $scheme) {
                            $index = $partyIdIndex + 1;
                            $this->updateNode($xpath, 
                                "$basePath/cac:DeliveryParty/cac:PartyIdentification[$index]/cbc:ID",
                                $id['ID']);
                            $partyIdIndex++;
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
        
        if (isset($data['PayeeFinancialAccount'][0]['ID'])) {
            $this->updateNode($xpath, "$basePath/cac:PayeeFinancialAccount/cbc:ID", 
                           $data['PayeeFinancialAccount'][0]['ID']);
        }
    }
    
    protected function updateTaxTotal(\DOMXPath $xpath, array $data): void
    {
        $basePath = "//cac:TaxTotal";
        
        $this->updateNode($xpath, "$basePath/cbc:TaxAmount", $data['TaxAmount'] ?? []);
        
        if (isset($data['TaxSubtotal'][0])) {
            $subtotal = $data['TaxSubtotal'][0];
            $subPath = "$basePath/cac:TaxSubtotal";
            
            $this->updateNode($xpath, "$subPath/cbc:TaxableAmount", $subtotal['TaxableAmount'] ?? []);
            $this->updateNode($xpath, "$subPath/cbc:TaxAmount", $subtotal['TaxAmount'] ?? []);
            
            if (isset($subtotal['TaxCategory'][0])) {
                $category = $subtotal['TaxCategory'][0];
                $catPath = "$subPath/cac:TaxCategory";
                
                $this->updateNode($xpath, "$catPath/cbc:ID", $category['ID'] ?? []);
                
                if (isset($category['TaxScheme'][0]['ID'])) {
                    $this->updateNode($xpath, "$catPath/cac:TaxScheme/cbc:ID", 
                                   $category['TaxScheme'][0]['ID']);
                }
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
        // First, remove existing InvoiceLine elements from template
        $existingLines = $xpath->query("//cac:InvoiceLine");
        foreach ($existingLines as $line) {
            $line->parentNode->removeChild($line);
        }
        
        // Get the parent element where InvoiceLines should be added
        $parentNode = $xpath->query("//cac:LegalMonetaryTotal")->item(0)->parentNode;
        
        // Add new InvoiceLine elements from JSON data
        foreach ($invoiceLines as $lineData) {
            $this->createInvoiceLine($xml, $parentNode, $lineData);
        }
    }
    
    protected function createInvoiceLine(\DOMDocument $xml, \DOMNode $parent, array $lineData): void
    {
        // Create InvoiceLine element
        $invoiceLine = $xml->createElement('cac:InvoiceLine');
        
        // Add ID
        if (isset($lineData['ID'][0]['_'])) {
            $id = $xml->createElement('cbc:ID', $lineData['ID'][0]['_']);
            $invoiceLine->appendChild($id);
        }
        
        // Add InvoicedQuantity
        if (isset($lineData['InvoicedQuantity'][0]['_'])) {
            $quantity = $xml->createElement('cbc:InvoicedQuantity', $lineData['InvoicedQuantity'][0]['_']);
            if (isset($lineData['InvoicedQuantity'][0]['unitCode'])) {
                $quantity->setAttribute('unitCode', $lineData['InvoicedQuantity'][0]['unitCode']);
            }
            $invoiceLine->appendChild($quantity);
        }
        
        // Add LineExtensionAmount
        if (isset($lineData['LineExtensionAmount'][0]['_'])) {
            $amount = $xml->createElement('cbc:LineExtensionAmount', $lineData['LineExtensionAmount'][0]['_']);
            if (isset($lineData['LineExtensionAmount'][0]['currencyID'])) {
                $amount->setAttribute('currencyID', $lineData['LineExtensionAmount'][0]['currencyID']);
            }
            $invoiceLine->appendChild($amount);
        }
        
        // Add AllowanceCharge
        if (isset($lineData['AllowanceCharge']) && is_array($lineData['AllowanceCharge'])) {
            foreach ($lineData['AllowanceCharge'] as $chargeData) {
                $charge = $xml->createElement('cac:AllowanceCharge');
                
                if (isset($chargeData['ChargeIndicator'][0]['_'])) {
                    $indicator = $xml->createElement('cbc:ChargeIndicator', 
                        $chargeData['ChargeIndicator'][0]['_'] ? 'true' : 'false');
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
        }
        
        // Add Item
        if (isset($lineData['Item'][0])) {
            $item = $this->createItem($xml, $lineData['Item'][0]);
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
                }
                $extension->appendChild($amount);
            }
            $invoiceLine->appendChild($extension);
        }
        
        // Add the InvoiceLine to parent
        $parent->appendChild($invoiceLine);
    }
    
    protected function createTaxTotal(\DOMDocument $xml, array $taxData): \DOMElement
    {
        $taxTotal = $xml->createElement('cac:TaxTotal');
        
        if (isset($taxData['TaxAmount'][0]['_'])) {
            $amount = $xml->createElement('cbc:TaxAmount', $taxData['TaxAmount'][0]['_']);
            if (isset($taxData['TaxAmount'][0]['currencyID'])) {
                $amount->setAttribute('currencyID', $taxData['TaxAmount'][0]['currencyID']);
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
                }
                $subtotal->appendChild($taxable);
            }
            
            // TaxAmount
            if (isset($subData['TaxAmount'][0]['_'])) {
                $taxAmount = $xml->createElement('cbc:TaxAmount', $subData['TaxAmount'][0]['_']);
                if (isset($subData['TaxAmount'][0]['currencyID'])) {
                    $taxAmount->setAttribute('currencyID', $subData['TaxAmount'][0]['currencyID']);
                }
                $subtotal->appendChild($taxAmount);
            }
            
            // Percent
            if (isset($subData['Percent'][0]['_'])) {
                $percent = $xml->createElement('cbc:Percent', $subData['Percent'][0]['_']);
                $subtotal->appendChild($percent);
            }
            
            // TaxCategory
            if (isset($subData['TaxCategory'][0])) {
                $category = $xml->createElement('cac:TaxCategory');
                $catData = $subData['TaxCategory'][0];
                
                // ID
                if (isset($catData['ID'][0]['_'])) {
                    $id = $xml->createElement('cbc:ID', $catData['ID'][0]['_']);
                    $category->appendChild($id);
                }
                
                // Percent
                if (isset($catData['Percent'][0]['_'])) {
                    $percent = $xml->createElement('cbc:Percent', $catData['Percent'][0]['_']);
                    $category->appendChild($percent);
                }
                
                // TaxExemptionReason
                if (isset($catData['TaxExemptionReason'][0]['_'])) {
                    $reason = $xml->createElement('cbc:TaxExemptionReason', 
                        $catData['TaxExemptionReason'][0]['_']);
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
                }
                
                $subtotal->appendChild($category);
            }
            
            $taxTotal->appendChild($subtotal);
        }
        
        return $taxTotal;
    }
    
    protected function createItem(\DOMDocument $xml, array $itemData): \DOMElement
    {
        $item = $xml->createElement('cac:Item');
        
        // Description
        if (isset($itemData['Description'][0]['_'])) {
            $desc = $xml->createElement('cbc:Description', $itemData['Description'][0]['_']);
            $item->appendChild($desc);
        }
        
        // CommodityClassification
        if (isset($itemData['CommodityClassification']) && is_array($itemData['CommodityClassification'])) {
            foreach ($itemData['CommodityClassification'] as $classification) {
                if (isset($classification['ItemClassificationCode'][0]['_'])) {
                    $class = $xml->createElement('cac:CommodityClassification');
                    $code = $xml->createElement('cac:ItemClassificationCode', 
                        $classification['ItemClassificationCode'][0]['_']);
                    if (isset($classification['ItemClassificationCode'][0]['listID'])) {
                        $code->setAttribute('listID', 
                            $classification['ItemClassificationCode'][0]['listID']);
                    }
                    $class->appendChild($code);
                    $item->appendChild($class);
                }
            }
        }
        
        return $item;
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
        }
    }
}