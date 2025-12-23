<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


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
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Str;

class eInvoisModel extends Model
{
    private $clientId;
    private $clientSecret;
    private $tinNo;
    private $prodMode;

    public function __construct($connection = null)
    {
  

        
        $connection='kd';

        
        if(Session('connection_integrate')!=''){
            $connection=Session('connection_integrate');
        }
        if ($connection) {
            $this->loadCredentials($connection);
        }
    }

    public function loadCredentials($connection)
    {

        $customer = DB::table('customer')->where('connection_integrate', $connection)
            ->whereNotNull('secret_key1')
            ->whereNotNull('secret_key2')
            ->whereNotNull('tin_no')
            ->first();

   
        if (!$customer) {
            throw new \Exception("Client credentials not found for connection: $connection");
        }

        $this->clientId = $customer->secret_key1;
        $this->clientSecret = $customer->secret_key2;
        $this->tinNo = $customer->tin_no;
        if(env('MYINVOIS_ENVIRONMENT', 'preprod')=='pre_prod'){
            $this->prodMode=false;
        }else{
            $this->prodMode=true;
        }
       
    }

    public function getClient()
    {
        
        return new MyInvoisClient($this->clientId, $this->clientSecret, $this->prodMode);
    }

    public function validate_tin($tin,$idType, $idValue){
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
        $response = $client->validateTaxPayerTin($tin, $idType, $idValue);
        return $response;
    }

    public function login()
    {
        $client = $this->getClient();
        $client->login($this->tinNo);
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);
        $client->setOnbehalfof($this->tinNo);
        return $client;
    }


    public function validateTaxPayerTin(
        string $tin,
        string $idType,
        string $idValue
    ) {
        $client = $this->getClient();
    
        // Login & token
        $client->login();
        $accessToken = $client->getAccessToken();
        $client->setAccessToken($accessToken);
    
        // On behalf of supplier TIN (important for middleware)
      
    
        return $client->validateTaxPayerTin(
            $tin,
            $idType,
            $idValue
        );
    }

    
    public function searchTaxPayerTin(
        ?string $taxPayerName,
        string $idType,
        string $idValue,
        ?string $fileType = null
    ) {
        $client = $this->getClient();
    
        // Login & token
        $client->login();
        $accessToken = $client->getAccessToken();
        $client->setAccessToken($accessToken);
    
        // On behalf of supplier TIN
       
        return $client->searchTaxPayerTin(
            $taxPayerName,
            $idType,
            $idValue,
            $fileType
        );
    }
    

public function qr_link($uuid)
{
    $client = $this->getClient();
    $client->login();

    $access_token = $client->getAccessToken();
    $client->setAccessToken($access_token);

    // Fetch document
    $response = $client->getDocument($uuid);

    // Extract Long ID
    $longId = $response['longID'] ?? null;

    DB::table('invoice')
        ->where('uuid', $uuid)
        ->update([
            'long_id' => $longId,
        ]);

    // Determine environment
    $useDb = env('USE_DB');

    if ($useDb === 'prod') {
        $base = env('MYINVOIS_PROD_URL');
    } else {
        $base = env('MYINVOIS_PREPROD_URL');
    }

    // Construct QR share link
    return "{$base}/{$uuid}/share/{$longId}";
}


public function submit($id_customer)
{
    try {

        $client = $this->getClient();
        $client->login();
        $client->setAccessToken($client->getAccessToken());

        /* =====================================================
         * CERTIFICATE VALIDATION (KEKAL)
         * ===================================================== */
        $certPath    = base_path('cert/certificate.crt');
        $privatePath = base_path('cert/private.key');

        if (!file_exists($certPath) || !file_exists($privatePath)) {
            throw new \Exception("Certificate files not found");
        }

        if (!is_readable($certPath) || !is_readable($privatePath)) {
            throw new \Exception("Certificate files are not readable");
        }

        $cert = openssl_x509_read(file_get_contents($certPath));
        if (!$cert) {
            throw new \Exception("Invalid certificate format");
        }

        $certInfo = openssl_x509_parse($cert);
        if ($certInfo['validTo_time_t'] < time()) {
            throw new \Exception("Certificate has expired");
        }

        $privateKey = openssl_pkey_get_private(
            file_get_contents($privatePath),
            env('PKCS12_PASSWORD')
        );

        if (!$privateKey || !openssl_x509_check_private_key($cert, $privateKey)) {
            throw new \Exception("Certificate and private key do not match");
        }

        /* =====================================================
         * LOAD SESSION & INVOICE
         * ===================================================== */
        $session             = session('invoice_unique_id');
        $consolidate_status  = session('consolidate_status');
        $invoice_type_code   = session('invoice_type_code');

        // 🔧 FIX: Self-Billed detection yang BETUL
        $isSelfBilled = in_array($invoice_type_code, ['11','12','13','14']);

        $record = DB::table('invoice')->where('unique_id', $session)->first();
        if (!$record) {
            throw new \Exception("Invoice record not found");
        }

        $id = $record->invoice_no;
        session(['invoice_id' => $record->invoice_no]);

        /* =====================================================
         * DATA (KEKAL SEMUA FIELD)
         * ===================================================== */
        $data = [
            'id_invoice' => $record->id_invoice,
            'invoice_status' => $record->invoice_status,
            'invoice_no' => $record->invoice_no,
            'invoice_type_code' => $invoice_type_code,
            'issue_date' => $record->issue_date,
            'price' => $record->price,
            'taxable_amount' => $record->taxable_amount,
            'tax_amount' => $record->tax_amount,

            // 🔧 FIX: Tax self-billed
            'tax_category_id' => $isSelfBilled ? 'OTH' : '01',
            'tax_scheme_id'   => $record->tax_scheme_id,
            'tax_percent'     => $isSelfBilled ? 0 : $record->tax_percent,

            'tax_exemption_reason' => $record->tax_exemption_reason,
            'payment_note_term' => $record->payment_note_term,
            'payment_financial_account' => $record->payment_financial_account,
            'include_signature' => $record->include_signature,
            'uuid' => $record->uuid,
            'long_id' => $record->long_id,
            'payment_method' => $record->payment_method,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];

        /* =====================================================
         * CUSTOMER ID (KEKAL LOGIC)
         * ===================================================== */
        if (empty($record->id_customer) || $consolidate_status == 1) {
            $customerId = 6;
        } else {
            $customerId = $record->id_customer;
        }

        /* =====================================================
         * 🔧 FIX: SUPPLIER / CUSTOMER SWAP (FIELD KEKAL)
         * ===================================================== */
        if ($isSelfBilled) {
            // buyer jadi supplier
            $supplierRow = DB::table('customer')->where('id_customer', $record->id_customer)->first();
            $customerRow = DB::table('customer')->where('id_customer', $record->id_supplier)->first();
        } else {
            $supplierRow = DB::table('customer')->where('id_customer', $record->id_supplier)->first();
            $customerRow = DB::table('customer')->where('id_customer', $customerId)->first();
        }

        if (!$supplierRow || !$customerRow) {
            throw new \Exception("Supplier / Customer record not found");
        }

        $supplier = [
            'tin_no' => $supplierRow->tin_no,
            'registration_name' => $supplierRow->registration_name,
            'phone' => $supplierRow->phone,
            'email' => $supplierRow->email,
            'city_name' => $supplierRow->city_name,
            'postal_zone' => $supplierRow->postal_zone,
            'country_subentity_code' => $supplierRow->country_subentity_code,
            'country_code' => $supplierRow->country_code,
            'address_line_1' => $supplierRow->address_line_1,
            'address_line_2' => $supplierRow->address_line_2,
            'address_line_3' => $supplierRow->address_line_3,
            'identification_type' => $supplierRow->identification_type,
            'identification_no' => $supplierRow->identification_no
        ];

        $customer = [
            'tin_no' => $customerRow->tin_no,
            'sst_registration' => $customerRow->sst_registration,
            'registration_name' => $customerRow->registration_name,
            'phone' => $customerRow->phone,
            'email' => $customerRow->email,
            'city_name' => $customerRow->city_name,
            'postal_zone' => $customerRow->postal_zone,
            'country_subentity_code' => $customerRow->country_subentity_code,
            'country_code' => $customerRow->country_code,
            'address_line_1' => $customerRow->address_line_1,
            'address_line_2' => $customerRow->address_line_2,
            'address_line_3' => $customerRow->address_line_3,
            'identification_type' => $customerRow->identification_type,
            'identification_no' => $customerRow->identification_no
        ];

        /* =====================================================
         * 🔧 FIX: DELIVERY
         * ===================================================== */
        if ($consolidate_status == 1 || $record->invoice_status == 'manual' || $isSelfBilled) {
            $delivery = '';
        } else {
            $delivery = $supplier;
        }

        /* =====================================================
         * ITEMS (KEKAL)
         * ===================================================== */
        $items = [];
        $invoiceItems = DB::table('invoice_item')->where('unique_id', $session)->get();

        foreach ($invoiceItems as $row) {
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
                'item_clasification_value' => $row->item_clasification_value
            ];
        }

        $data['items'] = $items;

        /* =====================================================
         * CREATE & SUBMIT DOCUMENT (KEKAL)
         * ===================================================== */
        $example = new CreateDocumentExample();

        $invoiceTypes = [
            '01' => InvoiceTypeCodes::INVOICE,
            '02' => InvoiceTypeCodes::CREDIT_NOTE,
            '03' => InvoiceTypeCodes::DEBIT_NOTE,
            '04' => InvoiceTypeCodes::REFUND_NOTE,
            '11' => InvoiceTypeCodes::SELF_BILLED_INVOICE,
            '12' => InvoiceTypeCodes::SELF_BILLED_CREDIT_NOTE,
            '13' => InvoiceTypeCodes::SELF_BILLED_DEBIT_NOTE,
            '14' => InvoiceTypeCodes::SELF_BILLED_REFUND_NOTE,
        ];

        $invoiceJson = $example->createJsonDocument(
            $invoiceTypes[$invoice_type_code],
            $record->invoice_no,
            $supplier,
            $customer,
            $delivery,
            true,
            $certPath,
            $privatePath,
            false,
            [
                'SigningTime' => date('Y-m-d\TH:i:s\Z'),
            ],
            $data
        );

        $document  = MyInvoisHelper::getSubmitDocument($id, $invoiceJson);
        $response  = $client->submitDocument([$document]);
        //print_r($invoiceJson);
        /* =====================================================
         * SAVE RESULT (KEKAL)
         * ===================================================== */
        if (!empty($response['acceptedDocuments'][0]['uuid'])) {

            DB::table('invoice')->where('unique_id', $session)->update([
                'submission_status' => 'submitted',
                'uuid' => $response['acceptedDocuments'][0]['uuid'],
                'submission_uuid' => $response['submissionUid']
            ]);

            DB::table('message_header')->insert([
                'document_id' => $record->invoice_no,
                'type_submission' => $invoice_type_code,
                'id_invoice' => $record->id_invoice,

                // 🔧 FIX: Hash JSON
                'hashing_256' => hash('sha256', json_encode($invoiceJson, JSON_UNESCAPED_SLASHES)),

                'supplier_tin' => $supplier['tin_no'] ?? null,
                'customer_tin' => $customer['tin_no'] ?? null,
                'status_submission' => 'SUBMITTED',
                'uuid' => $response['acceptedDocuments'][0]['uuid'],
                'submission_uuid' => $response['submissionUid'],
                'document_json' => json_encode($invoiceJson, JSON_PRETTY_PRINT),
                'request_json' => json_encode([$document]),
                'response_json' => json_encode($response),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('consolidate_invoice')->where('unique_id', $session)->update(['is_invoice' => 1]);
            DB::table('consolidate_invoice_item')->where('unique_id', $session)->update(['is_invoice' => 1]);

            return response()->json($response);
        }

        throw new \Exception(json_encode($response));

    } catch (\Exception $e) {

        \Log::error($e);

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

        return $client->cancelDocument($id, $reason);
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
