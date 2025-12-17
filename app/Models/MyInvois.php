<?php
namespace App\Models;
use Illuminate\Http\Request;
use KLH\MyInvois\MyInvois;
use KLH\MyInvois\Models\Invoice;
use Klsheng\Myinvois\MyInvoisClient;
use Klsheng\Myinvois\Example\Ubl\CreateDocumentExample;
use Klsheng\Myinvois\Ubl\Constant\InvoiceTypeCodes;
use Klsheng\Myinvois\Helper\MyInvoisHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class eInvoisModel extends Model
{
    private $clientId;
    private $clientSecret;
    private $prodMode;

    public function __construct()
    {
        $this->clientId = "68459bb8-ed45-4ea6-8846-5ba2740a5e2f";
        $this->clientSecret = "ed9d15f7-1886-48f7-b642-9d85ab995881";
        $this->prodMode = false;
    }

    private function getClient()
    {
        return new MyInvoisClient($this->clientId, $this->clientSecret, $this->prodMode);
    }
 
    public function qr_link($uuid){

        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
         // Submit or fetch your document (use your existing invoice UUID)
        // Example: retrieving an existing document using its UUID
    
        $response = $client->getDocument($uuid);
        
        // Extract the Long ID from the response
        $longId = $response['longID'] ?? null;
       

        // Determine the base URL
       // ? 'https://myinvois.hasil.gov.my'
       // : 'https://preprod.myinvois.hasil.gov.my';

        $base = 'https://preprod.myinvois.hasil.gov.my';
           
        // Construct the shareable link
        echo $link = "{$base}/{$uuid}/share/{$longId}";
        exit();
    }

    public function login()
    {
        $client = $this->getClient();
        $client->login('IG20868489010');
        $access_token = $client->getAccessToken();
        // Store $access_token somewhere to re-use it again within 1 hour

        // OR
        $client->setAccessToken('access_token');
        $client->setOnbehalfof('IG20868489010');
    }



    public function submit($id_customer)
    {

       
        
        try {

            $client = $this->getClient();
            $client->login();
            $access_token = $client->getAccessToken();
            $client->setAccessToken($access_token);
    
            $id = 'INV20240418105410';
            
            // ... existing supplier, customer, delivery, and data setup code ...
    
            // Verify certificate existence and permissions
            //$certPath = base_path('cert/certificate.crt');
            $certPath = base_path('cert/certificate.crt');
            $privatePath = base_path('cert/private.key');
            
            if (!file_exists($certPath) || !file_exists($privatePath)) {
                throw new \Exception("Certificate files not found");
            }
    
            // Verify certificate permissions
            if (!is_readable($certPath) || !is_readable($privatePath)) {
                throw new \Exception("Certificate files are not readable");
            }
    
            // Verify certificate validity
            $cert = openssl_x509_read(file_get_contents($certPath));
            if (!$cert) {
                throw new \Exception("Invalid certificate format");
            }
    
            // Check certificate expiration
            $certInfo = openssl_x509_parse($cert);
            if ($certInfo['validTo_time_t'] < time()) {
                throw new \Exception("Certificate has expired");
            }
            $password = env('PKCS12_PASSWORD');
            // Verify private key matches certificate
            $privateKey = openssl_pkey_get_private(file_get_contents($privatePath),  $password);
            if (!$privateKey) {
                throw new \Exception("Invalid private key or passphrase");
            }
    
            // Verify key pair matches
            if (!openssl_x509_check_private_key($cert, $privateKey)) {
                throw new \Exception("Certificate and private key do not match");
            }
    
            $id = 'INV20240418105410';
            $session ="78e7564-d101-4194-b813-5a2c6ae9d656";
            $consolidate_status = '';
            
            $record = DB::table('invoice')->where('unique_id', $session)->first();
            session(['invoice_id' => $record->invoice_no]);
            $data = [
                'id_invoice' => $record->id_invoice,
                'invoice_status' => $record->invoice_status,
                'invoice_no' => $record->invoice_no,
                'invoice_type_code' => '11',
                'issue_date' => $record->issue_date,
                'price' => $record->price,
                'taxable_amount' => $record->taxable_amount,
                'tax_amount' => $record->tax_amount,
                'tax_category_id' => $record->tax_category_id,
                'tax_exemption_reason' => $record->tax_exemption_reason,
                'tax_scheme_id' => $record->tax_scheme_id,
                'tax_percent' => $record->tax_percent,
                'payment_note_term' => $record->payment_note_term,
                'payment_financial_account' => $record->payment_financial_account,
                'include_signature' => $record->include_signature,
                'uuid' => $record->uuid,
                'long_id' => $record->long_id,
                'payment_method' => $record->payment_method,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ];
        
            if(empty($record->id_customer)){
                $customer=8;
            }else{
                $customer=$record->id_customer;
            }
   
            $supplierCustomer = DB::table('customer')->where('id_customer', $record->id_supplier)->first(); // Adjust ID as needed
         
            // 2. Transform DB record into array
            $supplier = [
                'tin_no' => $supplierCustomer->tin_no,
                'NRIC' => $supplierCustomer->identification_no,
                'BRN' => $supplierCustomer->sst_registration,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];
        
            $supplierCustomer = DB::table('customer')->where('id_customer', $customer)->first(); // Adjust ID as needed
          
            // 2. Transform DB record into array
            $customer = [
                'tin_no' => $supplierCustomer->tin_no,
                'sst_registration' => $supplierCustomer->sst_registration,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];
        
            $supplierCustomer = DB::table('customer')->where('id_customer',$record->id_supplier)->first(); // Adjust ID as needed
           
            $delivery = [
                'tin_no' => $supplierCustomer->tin_no,
                'registration_name' => $supplierCustomer->registration_name,
                'phone' => $supplierCustomer->phone,
                'email' => $supplierCustomer->email,
                'city_name' => $supplierCustomer->city_name,
                'postal_zone' => $supplierCustomer->postal_zone,
                'country_subentity_code' => $supplierCustomer->country_subentity_code,
                'country_code' => $supplierCustomer->country_code,
                'address_line_1' => $supplierCustomer->address_line_1,
                'address_line_2' => $supplierCustomer->address_line_2,
                'address_line_3' => $supplierCustomer->address_line_3,
                'identification_type' => $supplierCustomer->identification_type,
                'identification_no' => $supplierCustomer->identification_no
            ];

            $invoiceItems = DB::table('invoice_item')->where('unique_id', $session)->get();
           
            $items = [];
            
            foreach ($invoiceItems as $row) {
               // echo $item->id_invoice_item;
                $items[] = [
                    'id_invoice_item' => $row->id_invoice_item,
                    'id_customer' => $row->id_customer,
                    'id_invoice' => $row->id_invoice,
                    'price_discount' => $row->price_discount,
                    'line_id' => $row->line_id,
                    'invoiced_quantity' => $row->invoiced_quantity,
                    'line_extension_amount' => $row->line_extension_amount,
                    'item_description' => $row->item_description,
                    'price_amount' => $row->price_amount,
                    'price_extension_amount' => $row->price_extension_amount,
                    'item_clasification_value'=>$row->item_clasification_value
                ];
            }
      
            $data['items'] = $items;
            if($consolidate_status==1){
            $delivery='';
            }
            
            /*case InvoiceTypeCodes::CREDIT_NOTE:
                return new CreditNote();
                break;
            case InvoiceTypeCodes::DEBIT_NOTE:
                return new DebitNote();
                break;
            case InvoiceTypeCodes::REFUND_NOTE:
                return new RefundNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_INVOICE:
                return new SelfBilledInvoice();
                break;
            case InvoiceTypeCodes::SELF_BILLED_CREDIT_NOTE:
                return new SelfBilledCreditNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_DEBIT_NOTE:
                return new SelfBilledDebitNote();
                break;
            case InvoiceTypeCodes::SELF_BILLED_REFUND_NOTE:
                return new SelfBilledRefundNote();
                break;
            default:
                return new Invoice();
                break;*/


            $example = new CreateDocumentExample();
            $invoice = $example->createJsonDocument(
                InvoiceTypeCodes::CREDIT_NOTE,
                $id,
                $supplier,
                $customer,
                $delivery,
                true,
                $certPath,
                $privatePath,
                false,
                [
                    'SigningTime' => date('Y-m-d\TH:i:s\Z'),
                    'DigestMethod' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                    'SignatureMethod' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
                ],
                $data
            );
       
            $documents = [];
            $document = MyInvoisHelper::getSubmitDocument($id, $invoice);
            $documents[] = $document;
            //echo $document;
            // echo hash('sha256', $invoice);
            //print_r($document);
            //echo $invoice;

            $response = $client->submitDocument($documents);
      
            session(['consolidate_status' => '']);
            session(['invoice_id' => '']);
      
            //$invoice=$this->compareDigestValues($invoice);
           // echo $invoice;
            if (!empty($response['submissionUid']) && !empty($response['acceptedDocuments'][0]['uuid'])) {
                // ... existing success handling code ...
                // Record failure in message_header
                //echo $longId = $client->getDocument($response['acceptedDocuments'][0]['uuid']);
        
                // Extract the Long ID from the response
                //$longId = $longId['longID'] ?? null;

                DB::table('invoice')
                ->where('unique_id', $session) // match using unique_id
                ->update([
                'uuid' =>  $response['acceptedDocuments'][0]['uuid'] ?? null,
                'submission_uuid' => $response['submissionUid'] ?? null
                
            ]);

            DB::table('message_header')->insert([
                'document_id' => $record->invoice_no?? null,
                'type_submission' => 'INVOICE',
                'id_invoice' => $record->id_invoice,
                'hashing_256'=>hash('sha256', $invoice),
                'supplier_tin' => $supplier['tin_no'] ?? null,
                'customer_tin' => $customer['tin_no'] ?? null,
                'status_submission' => 'SUBMITTED',
                'submission_uuid' => $response['submissionUid'] ?? null,
                'uuid' => $response['acceptedDocuments'][0]['uuid'] ?? null,
                'error_message' => '',
                'submission_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'document_json' => json_encode($invoice , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'request_json' => json_encode($documents ?? []),
                'response_json' => json_encode($response ?? [])
            ]);
    
            } else if (!empty($response['errors'])) {
                throw new \Exception("Document submission failed: " . json_encode($response['errors']));
            }
    
            return response()->json($response);
    
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Document submission failed: ' . $e->getMessage());
            
            $e->getMessage();
   
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        
    }



    public function cancelDocument(string $id, string $reason = 'Customer refund')
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->cancelDocument($id, $reason);
    }

    public function rejectDocument(string $id, string $reason = 'Customer reject')
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
   

        return $this->client->rejectDocument($id, $reason);
    }

    public function getRecentDocuments(
        int $pageNo = 1,
        int $pageSize = 20,
        ?string $submissionDateFrom = null,
        ?string $submissionDateTo = null,
        ?string $issueDateFrom = null,
        ?string $issueDateTo = null,
        string $direction = 'Sent',
        string $status = 'Valid',
        ?string $documentType = '01',
        ?string $receiverId = null,
        ?string $receiverIdType = null,
        ?string $receiverTin = null,
        ?string $issuerId = null,
        ?string $issuerIdType = null,
        ?string $issuerTin = null
    ) {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getRecentDocuments(
            $pageNo, $pageSize,
            $submissionDateFrom, $submissionDateTo,
            $issueDateFrom, $issueDateTo,
            $direction, $status, $documentType,
            $receiverId, $receiverIdType, $receiverTin,
            $issuerId, $issuerIdType, $issuerTin
        );
    }

    public function getSubmission(string $id, int $pageNo = 1, int $pageSize = 100)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getSubmission($id, $pageNo, $pageSize);
    }

    public function getDocument(string $id)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getDocument($id);
    }

    public function getDocumentDetail(string $id)
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->getDocumentDetail($id);
    }

    public function searchDocuments(
        ?string $id = null,
        ?\DateTime $submissionDateFrom = null,
        ?string $submissionDateTo = null,
        int $pageNo = 1,
        int $pageSize = 100,
        ?string $issueDateFrom = null,
        ?string $issueDateTo = null,
        string $direction = 'Sent',
        string $status = 'Valid',
        ?string $documentType = '01',
        ?string $searchQuery = null
    ) {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->searchDocuments(
            $id,
            $submissionDateFrom,
            $submissionDateTo,
            $pageNo,
            $pageSize,
            $issueDateFrom,
            $issueDateTo,
            $direction,
            $status,
            $documentType,
            $searchQuery
        );
    }

    public function generateDocumentQrCodeUrl(string $id, string $longId): string
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $this->client->generateDocumentQrCodeUrl($id, $longId);
    }
}
?>
