
public function submitInvoiceAsIntermediary()
    {
        $certPath = base_path('cert/certificate.crt');
        $privatePath = base_path('cert/private.key');

        $sdk = new MyInvois([
            'cert' => $certPath,
            'key' => $privatePath,
            'key_pass' => 'Ks5#4de0',
            'sandbox' => true,
            'intermediary_id' => 'IG20868489010'
        ]);

        $invoice = new Invoice([
            'ProfileID' => 'MyInvois',
            'ID' => 'INV-001',
            'IssueDate' => date('Y-m-d'),
            'InvoiceTypeCode' => '01',
            'DocumentCurrencyCode' => 'MYR',
            'Seller' => [
                'ID' => 'IG20868489010',
                'Name' => 'FAESAL AMAR BIN JAAFAR',
                'Address' => 'No. 1, Jalan Test, 40100 Shah Alam, Selangor',
                'Contact' => [
                    'ElectronicMail' => 'admin@xideasoft.com'
                ]
            ],
            'Buyer' => [
                'ID' => 'C23704752070',
                'Name' => 'Grabcar Sdn Bhd',
                'Address' => 'No. 2, Jalan Buyer, 50000 KL',
                'Contact' => [
                    'ElectronicMail' => 'buyer@test.com'
                ]
            ],
            'DocumentProvenance' => [
                'SubmitterID' => 'MYINTER123456789',
                'SubmitterType' => 'IN'
            ],
            'InvoiceLine' => [
                [
                    'ID' => '1',
                    'InvoicedQuantity' => [
                        '_' => 2,
                        'unitCode' => 'C62'
                    ],
                    'Item' => [
                        'Name' => 'Item A',
                        'Description' => 'Contoh Item A'
                    ],
                    'Price' => [
                        'PriceAmount' => [
                            '_' => 100,
                            'currencyID' => 'MYR'
                        ]
                    ],
                    'LineExtensionAmount' => [
                        '_' => 200,
                        'currencyID' => 'MYR'
                    ]
                ]
            ],
            'LegalMonetaryTotal' => [
                'PayableAmount' => [
                    '_' => 200,
                    'currencyID' => 'MYR'
                ]
            ]
        ]);

        $response = $sdk->invoice()->submit($invoice);

        return response()->json($response);
    }


public function generateFromTemplate(int $invoiceId)
    {
        $invoice = DB::table('invoice')->where('id_invoice', $invoiceId)->first();
        $customer = DB::table('customer')->where('id_customer', 3)->first();
        $supplier = DB::table('customer')->where('id_customer', 2)->first();
        $items = DB::table('invoice_item')->where('id_invoice', $invoiceId)->get();
    
        if (!$invoice || !$customer || !$supplier || $items->isEmpty()) {
            return response()->json(['error' => 'Missing data'], 422);
        }
    
        $file_template = base_path('template/1.1-Invoice-Sample.json');
        $template = json_decode(file_get_contents($file_template), true);
    
        $doc =& $template['Invoice'][0];
        $doc['ID'][0]['_'] = $invoice->invoice_no;
        $doc['IssueDate'][0]['_'] = date('Y-m-d'); // Use current date
        $doc['IssueTime'][0]['_'] = date('H:i:s') . 'Z'; // Use current time
    
    
        $partyIdentifications = [
            [
                'ID' => [[
                    '_' => $customer->tin_no,
                    'schemeID' => 'TIN'
                ]]
            ]
        ];
        
       
            $partyIdentifications[] = [
                'ID' => [[
                    '_' => $customer->identification_no,
                    'schemeID' => $customer->identification_type
                ]]
            ];
        
        
        // Tambah SST dan TTX sebagai placeholder jika perlu
        $partyIdentifications[] = [
            'ID' => [[
                '_' => 'NA',
                'schemeID' => 'SST'
            ]]
        ];
        $partyIdentifications[] = [
            'ID' => [[
                '_' => 'NA',
                'schemeID' => 'TTX'
            ]]
        ];
    
    
        $supplierIdentifications = [
            [
                'ID' => [[
                    '_' => $supplier->tin_no,
                    'schemeID' => 'TIN'
                ]]
            ]
        ];
        
       
            $supplierIdentifications[] = [
                'ID' => [[
                    '_' => $supplier->identification_no,
                    'schemeID' => $supplier->identification_type
                ]]
            ];
        
        
        $supplierIdentifications[] = [
            'ID' => [[
                '_' => 'NA',
                'schemeID' => 'SST'
            ]]
        ];
        $supplierIdentifications[] = [
            'ID' => [[
                '_' => 'NA',
                'schemeID' => 'TTX'
            ]]
        ];
    
    
    
    
        $doc['AccountingCustomerParty'][0]['Party'][0]['PartyLegalEntity'] = [
            [
                'RegistrationName' => [
                    ['_' => $customer->registration_name]
                ]
            ]
        ];
        
        $customerIdentifications = [
            [
                'ID' => [
                    [
                        '_' => $customer->tin_no,
                        'schemeID' => 'TIN'
                    ]
                ]
            ]
        ];
        
        // Add BRN if exists
        if (!empty($customer->identification_no) && !empty($customer->identification_type)) {
            $customerIdentifications[] = [
                'ID' => [
                    [
                        '_' => $customer->identification_no,
                        'schemeID' => $customer->identification_type
                    ]
                ]
            ];
        }
        
        // Add SST and TTX defaults
        $customerIdentifications[] = [
            'ID' => [
                [
                    '_' => 'NA',
                    'schemeID' => 'SST'
                ]
            ]
        ];
        $customerIdentifications[] = [
            'ID' => [
                [
                    '_' => 'NA',
                    'schemeID' => 'TTX'
                ]
            ]
        ];
        
        $doc['AccountingSupplierParty'][0]['Party'][0]['PartyIdentification'] = $supplierIdentifications;
        
        $doc['AccountingSupplierParty'][0]['Party'][0]['PartyIdentification'][0]['ID'][0]['_'] = $supplier->tin_no;
        $doc['AccountingSupplierParty'][0]['Party'][0]['PartyLegalEntity'][0]['RegistrationName'][0]['_'] = $supplier->registration_name;
        $doc['AccountingSupplierParty'][0]['Party'][0]['Contact'][0]['Telephone'][0]['_'] = $supplier->phone;
        $doc['AccountingSupplierParty'][0]['Party'][0]['Contact'][0]['ElectronicMail'][0]['_'] = $supplier->email;
    
    
        $doc['AccountingCustomerParty'][0]['Party'][0]['PartyIdentification']= $customerIdentifications;
        $doc['AccountingCustomerParty'][0]['Party'][0]['PartyIdentification'][0]['ID'][0]['_'] = $customer->tin_no;
    
        
        //$doc['AccountingCustomerParty'][0]['Party'][0]['PartyLegalEntity'][0]['PartyIdentification']= $customerIdentifications;
        //$doc['AccountingCustomerParty'][0]['Party'][0]['PartyLegalEntity'][0]['RegistrationName'][0]['_'] = $customer->registration_name;
        $doc['AccountingCustomerParty'][0]['Party'][0]['Contact'][0]['Telephone'][0]['_'] = $customer->phone;
        $doc['AccountingCustomerParty'][0]['Party'][0]['Contact'][0]['ElectronicMail'][0]['_'] = $customer->email;
    
    
    
        // Delivery Party Info
        $doc['Delivery'][0]['DeliveryParty'][0] = [
            'PartyLegalEntity' => [[
                'RegistrationName' => [['_' => $customer->registration_name]]
            ]],
            'PostalAddress' => [[
                'CityName' => [['_' => $customer->city_name]],
                'PostalZone' => [['_' => $customer->postal_zone]],
                'CountrySubentityCode' => [['_' => $customer->country_subentity_code]],
                'AddressLine' => [
                    ['Line' => [['_' => $customer->address_line_1]]],
                    ['Line' => [['_' => $customer->address_line_2]]],
                    ['Line' => [['_' => $customer->address_line_3]]]
                ],
                'Country' => [[
                    'IdentificationCode' => [[
                        '_' => $customer->country_code ?? 'MYS',
                        'listID' => 'ISO3166-1',
                        'listAgencyID' => '6'
                    ]]
                ]]
            ]],
            'PartyIdentification' => array_filter([
                ['ID' => [['_' => $customer->tin_no, 'schemeID' => 'TIN']]],
                (!empty($customer->identification_no) && !empty($customer->identification_type)) ?
                    ['ID' => [['_' => $customer->identification_no, 'schemeID' => $customer->identification_type]]] : null
            ])
        ];
    
        $doc['InvoiceLine'] = [];
        foreach ($items as $idx => $item) {
            $doc['InvoiceLine'][] = [
                'ID' => [['_' => (string)($idx + 1)]], // Ensure ID is a string
                'InvoicedQuantity' => [['_' => (float)($item->quantity ?? 0), 'unitCode' => 'C62']],
                'LineExtensionAmount' => [['_' => (float)($item->price ?? 0) * (float)($item->quantity ?? 0), 'currencyID' => 'MYR']],
                'Item' => [
                    [
                        'Description' => [['_' => $item->description ?? 'Default Description']],
                        'CommodityClassification' => [[
                            'ItemClassificationCode' => [['_' => $item->classification ?? '003', 'listID' => 'CLASS']]
                        ]]
                    ]
                ],
                'Price' => [['PriceAmount' => [['_' => (float)($item->price ?? 0), 'currencyID' => 'MYR']]]],
                'ItemPriceExtension' => [['Amount' => [['_' => (float)($item->price ?? 0) * (float)($item->quantity ?? 0), 'currencyID' => 'MYR']]]]
            ];
        }
    
        $doc['LegalMonetaryTotal'][0]['PayableAmount'][0]['_'] = (float)($invoice->total ?? 0);
    
        $unsignedJson = json_encode($template, JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $unsignedJson, true);
        $digest = base64_encode($hash);
    
        $privateKeyPath = base_path('cert/private.key');
        if (!file_exists($privateKeyPath) || !is_readable($privateKeyPath)) {
            throw new Exception("Private key file not found or not readable at: $privateKeyPath");
        }
    
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent, 'your-passphrase-here');
    
        if (!$privateKey) {
            throw new Exception("Failed to load private key. Please check the key file, its format, and the passphrase.");
        }
    
    
         // Add mandatory Tax Types field
         $doc['TaxTotal'] = [[
            'TaxAmount' => [['_' => (float)($invoice->tax_amount ?? 0), 'currencyID' => 'MYR']],
            'TaxSubtotal' => [[
                'TaxableAmount' => [['_' => (float)($invoice->taxable_amount ?? 0), 'currencyID' => 'MYR']],
                'TaxAmount' => [['_' => (float)($invoice->tax_amount ?? 0), 'currencyID' => 'MYR']],
                'TaxCategory' => [[
                    'ID' => [['_' => '01', 'schemeID' => 'UN/ECE 5305']],
                    'Percent' => [['_' => (float)($invoice->tax_percent ?? 0)]],
                    'TaxScheme' => [[
                        'ID' => [['_' => 'OTH', 'schemeID' => 'UN/ECE 5153']],
                        'Name' => [['_' => 'Goods and Services Tax']],
                        'TaxTypeCode' => [['_' => 'GST']]
                    ]]
                ]]
            ]]
        ]];
    
        if (!openssl_sign($unsignedJson, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Failed to sign the data. Please verify the private key and input data.");
        }
    
        openssl_free_key($privateKey);
    
        $certContent = base_path('cert/certificate.crt');
        $certContent = file_get_contents($certContent);
        $certClean = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"], '', $certContent);
    
        $certResource = openssl_x509_read($certContent);
        $certInfo = openssl_x509_parse($certResource);
    
        $issuerNameParts = [];
        foreach ($certInfo['issuer'] as $key => $value) {
            $issuerNameParts[] = "$key=$value";
        }
        $issuerName = implode(', ', $issuerNameParts);
        $serialNumber = $certInfo['serialNumber'] ?? '';
    
        $signatureValue = base64_encode($signature);
    
        $UBLExtensionBlock = [
            'UBLExtension' => [[
                'ExtensionContent' => [[
                    'UBLDocumentSignatures' => [[
                        'Signature' => [[
                            '@Id' => 'signature',
                            'SignedInfo' => [[
                                'CanonicalizationMethod' => [['Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315']],
                                'SignatureMethod' => [['Algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256']],
                                'Reference' => [[
                                    'URI' => '#id-xades-signed-props',
                                    'Type' => 'http://uri.etsi.org/01903#SignedProperties',
                                    'DigestMethod' => [['Algorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256']],
                                    'DigestValue' => [['_' => $digest]]
                                ]]
                            ]],
                            'SignatureValue' => [['_' => $signatureValue]],
                            'KeyInfo' => [[
                                'X509Data' => [[
                                    'X509Certificate' => [['_' => $certClean]],
                                    'X509IssuerSerial' => [[
                                        'X509IssuerName' => [['_' => $issuerName]],
                                        'X509SerialNumber' => [['_' => (string) $serialNumber]]
                                    ]]
                                ]]
                            ]],
                            'Object' => [[
                                'QualifyingProperties' => [[
                                    '@Target' => '#signature',
                                    'SignedProperties' => [[
                                        '@Id' => 'id-xades-signed-props',
                                        'SignedSignatureProperties' => [[
                                            'SigningTime' => [['_' => now()->toAtomString()]]
                                        ]]
                                    ]]
                                ]]
                            ]]
                        ]]
                    ]]
                ]]
            ]]
        ];
    
        $template['Invoice'][0]['UBLExtensions'] = [$UBLExtensionBlock]; // Limit UBLExtensions to one item
        $id = 'INV20240418105410';
    
       echo $template= json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
        $documents = [];
                $document = MyInvoisHelper::getSubmitDocument($id, $template);
                $documents[] = $document;
                $client = $this->getClient();
                $client->login();
                $access_token = $client->getAccessToken();
                $client->setAccessToken($access_token);
        
    
                $response = $client->submitDocument($documents);
          print_r($response);     
        DB::table('message_header')->insert([
            'document_id' => $invoice->invoice_no,
            'type_submission' => 'INVOICE',
            'id_invoice' => $invoice->id_invoice,
            'hashing_256' => hash('sha256', json_encode($template)),
            'supplier_tin' => $supplier->tin_no,
            'customer_tin' => $customer->tin_no,
            'status_submission' => 'SUBMITTED',
            'submission_uuid' => 'simulated-submission',
            'uuid' => 'simulated-uuid',
            'error_message' => '',
            'submission_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            'document_json' => json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'request_json' => json_encode(["document" => $unsignedJson]),
            'response_json' => json_encode(['uuid' => 'simulated-uuid'])
        ]);
    
       
    }
    
