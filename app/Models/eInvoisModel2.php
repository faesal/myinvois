<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Http;
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

        if(Session('connection_integrate')!=''){
             $connection=Session('connection_integrate');
        }
        if ($connection) {
            $this->loadCredentials($connection);
        }
    }

    public function checkExpired($connCode){

        $supplier = DB::table('customer')
                    ->where('connection_integrate', $connCode)
                    ->where('customer_type', 'SUPPLIER')
                    ->where(function ($q) {
                        $q->whereNull('start_subscribe')
                        ->orWhere('start_subscribe', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_subscribe')
                        ->orWhere('end_subscribe', '>=', now());
                    })
                    ->first();
     
        if (!$supplier) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Subscription expired or inactive.'
            ], 403);
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
        $token =$client->setAccessToken($accessToken);
        //print_r($accessToken);
        // On behalf of supplier TIN (important for middleware)
      
  
        if(env('MYINVOIS_ENVIRONMENT', 'preprod')=='pre_prod'){
            $url=env('MYINVOIS_PREPROD_URL');
        }else{
            $url=env('MYINVOIS_PROD_URL');
        }
       
        $response = Http::withToken($accessToken)
        ->acceptJson()
        ->get(
            $url . "/api/v1.0/taxpayer/validate/{$tin}",
            [
                'idType'  => $idType,
                'idValue' => $idValue,
            ]
        );

    return response()->json($response->json(), $response->status());


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
    

    public function longID($unique_id)
    {
        // =====================================================
        // 1. Ambil invoice dari DB
        // =====================================================
        $invoice = DB::table('invoice')
            ->where('unique_id', $unique_id)
            ->first();
    
        if (!$invoice) {
            abort(404, 'Invoice not found');
        }
    
        // =====================================================
        // 2. Guard: Jika UUID / unique_id tiada
        // =====================================================
     
    
        // =====================================================
        // 3. Jika long_id sudah ada → terus return QR
        // =====================================================
        if (!empty($invoice->long_id)) {
            $base = env('USE_DB') === 'prod'
                ? env('MYINVOIS_PROD_URL')
                : env('MYINVOIS_PREPROD_URL');
    
            //return "{$base}/{$invoice->uuid}/share/{$invoice->long_id}";
        }
    
        // =====================================================
        // 4. Login MyInvois Client
        // =====================================================
        $client = $this->getClient();
        $client->login();
    
        $client->setAccessToken($client->getAccessToken());
    
        // =====================================================
        // 5. Fetch document dari LHDN
        // =====================================================
        $response = $client->getDocument($invoice->uuid);
    
        $longId = $response['longID'] ?? null;
    
     
    
        // =====================================================
        // 6. Simpan long_id
        // =====================================================
        DB::table('invoice')
            ->where('id_invoice', $invoice->id_invoice)
            ->update([
                'long_id' => $longId,
            ]);
       
        // =====================================================
        // 7. Return QR Share Link
        // =====================================================
        $base = env('USE_DB') === 'prod'
            ? env('MYINVOIS_PROD_URL')
            : env('MYINVOIS_PREPROD_URL');
    
       // return "{$base}/{$invoice->unique_id}/share/{$longId}";
    }

    public function qr_link_lhdn($unique_id)
    {
        try {
    
            $invoice = DB::table('invoice')
                ->where('unique_id', $unique_id)
                ->first();
    
            if (!$invoice) {
                return response()->json(['error' => 'Invoice not found'], 404);
            }
    
            if (empty($invoice->uuid)) {
                return response()->json(['error' => 'UUID empty'], 422);
            }
    
            if (!empty($invoice->long_id)) {
                $base = env('USE_DB') === 'prod'
                    ? env('MYINVOIS_PROD_URL')
                    : env('MYINVOIS_PREPROD_URL');
    
                echo "{$base}/{$invoice->uuid}/share/{$invoice->long_id}";
                exit();
            }
    
            Session::put('connection_integrate', $invoice->connection_integrate);
    
            $model  = new eInvoisModel($invoice->connection_integrate);
            $client = $model->getClient();
    
            $client->login();
            $client->setAccessToken($client->getAccessToken());
            
            $response = $client->getDocument($invoice->uuid);
    
            if (!is_array($response)) {
                throw new \Exception('Invalid response from LHDN');
            }
    
            $longId = $response['longID'] ?? null;
    
            
    
            if (!$longId) {
                 response()->json([
                    'status' => false,
                    'message' => 'LHDN still processing',
                    'response' => $response
                ], 202);
            }
    
            DB::table('invoice')
                ->where('id_invoice', $invoice->id_invoice)
                ->update([
                    'long_id' => $longId
                ]);
    
            $base = env('USE_DB') === 'prod'
                ? env('MYINVOIS_PROD_URL')
                : env('MYINVOIS_PREPROD_URL');
    
            echo "{$base}/{$invoice->uuid}/share/{$longId}";
    
        } catch (\Throwable $e) {
    
            echo response()->json([
                'status' => false,
                'error'  => true,
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
                'message'=> $e->getMessage()
            ], 500);
        }
    }
    
    
    
public function qr_link($uuid)
{

    $connection_integrate=Session('connection_integrate');
    $model = new eInvoisModel($connection_integrate);

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


        $client = $this->getClient();
       /// print_r($client);
        //exit();
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
            'total_price_discount'     =>$record->total_price_discount,
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
            'msicCode' => $supplierRow->msicCode,
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
        if ($consolidate_status == 1 || $record->invoice_status == 'manual' || $isSelfBilled || $customerRow->tin_no=='EI00000000010' ) {
            $delivery = '';
        } else {
            $delivery = $customer;
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
                'tax' => $row->tax,
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
      
         $MessageID= DB::table('message_header')->insertGetId([
            'document_id' => $record->invoice_no,
            'type_submission' => $invoice_type_code,
            'id_invoice' => $record->id_invoice,

            // 🔧 FIX: Hash JSON
            'hashing_256' => hash('sha256', json_encode($invoiceJson, JSON_UNESCAPED_SLASHES)),

            'supplier_tin' => $supplier['tin_no'] ?? null,
            'customer_tin' => $customer['tin_no'] ?? null,
            'status_submission' => 'SUBMITTED',
            'uuid' => @$response['acceptedDocuments'][0]['uuid'],
            'submission_uuid' => @$response['submissionUid'],
            'document_json' => json_encode($invoiceJson, JSON_PRETTY_PRINT),
            'request_json' => json_encode([$document]),
            'response_json' => json_encode($response),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
        if (!empty($response['acceptedDocuments'][0]['uuid'])) {

            DB::table('invoice')->where('unique_id', $session)->update([
                'submission_status' => 'submitted',
                'uuid' => $response['acceptedDocuments'][0]['uuid'],
                'submission_uuid' => $response['submissionUid']
            ]);

            
            DB::table('consolidate_invoice')->where('unique_id', $session)->update(['is_invoice' => 1]);
            DB::table('consolidate_invoice_item')->where('unique_id', $session)->update(['is_invoice' => 1]);
            //$this->qr_link_lhdn($session);
            return response()->json($response);
        }

        return response()->json([
            'status' => 'error',
            'message' => json_encode($response)
        ], 400);
       exit();

    } catch (\Exception $e) {

        \Log::error($e);

        DB::table('message_header')->where('id', $MessageID)->update(['error_message' => $e->getMessage()]);

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}




    public function cancelDocument(string $uuid, string $reason = 'Cancel')
    {
        $client = $this->getClient();
        $client->login();
        $access_token = $client->getAccessToken();
        $client->setAccessToken($access_token);

        return $client->cancelDocument($uuid, $reason);
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

    public function processInvoice2(Request $request,$type)
    {

        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');
        $item_clasification_code ='022';

        if($type=='GENERAL'){

        

            if($tin=='EI00000000010'){
                $item_clasification_code ='004';
                $identification_no = 'NA';
                $identification_type = 'BRN';
                /*$session= session([
                    'consolidate_status' =>1
                ]);*/

            }else if($tin=='EI00000000020'){
                $item_clasification_code ='022';
                $identification_no = 'NA';
                $identification_type = 'BRN';
            }else if($tin=='EI00000000030'){
                return response()->json([
                    'status' => 'error',
                    'message' => 'LHDN :This TIN No. is only valid for selfbill supplier'
                ], 422);
            }else if($tin=='EI00000000040'){
                $item_clasification_code ='022';
                $identification_no = 'NA';
                $identification_type = 'BRN';
            }

        }else{
            $identification_no=data_get($customerPayload, 'identification_no');
            $identification_type=data_get($customerPayload, 'identification_type');
            $item_clasification_code='022';
        }

        $payload = json_decode($request->getContent(), true);
    
        if (!is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON received'
            ], 400);
        }
        $isAutoToLHDN    = data_get($payload, 'isAutoToLHDN');
        // =====================================================
        // 1. AUTH
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
    
        $connCode = $client->code;
        
        // =====================================================
        // 2. SUPPLIER
        // =====================================================
        $model = new \App\Models\eInvoisModel;
    
        // =====================================================
        // 3. CUSTOMER (UPSERT + STATUS)
        // =====================================================
        $customerPayload = data_get($payload, 'customer');
    
        if (!data_get($customerPayload, 'tin_no')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'customer.tin_no is required'
            ], 422);
        }


        
    
        $customer = DB::table('customer')
            ->where('connection_integrate', $connCode)
            ->where('tin_no', data_get($customerPayload, 'tin_no'))
            ->where('customer_type', 'CUSTOMER')
            ->first();
    
    
        $supplier = DB::table('customer')
            ->where('connection_integrate', $connCode)
            ->where('customer_type', 'SUPPLIER')
            ->first();
        
        if ($customer) {
    
            // ================= UPDATE CUSTOMER =================
            DB::table('customer')
                ->where('id_customer', $customer->id_customer)
                ->update([
                    'registration_name'    => data_get($customerPayload, 'registration_name'),
                    'identification_no'    => $identification_no,
                    'identification_type'  => $identification_type,
                    'phone'                => data_get($customerPayload, 'phone'),
                    'email'                => data_get($customerPayload, 'email'),
                    'address_line_1'       => data_get($customerPayload, 'address_line_1'),
                    'address_line_2'       => data_get($customerPayload, 'address_line_2'),
                    'address_line_3'       => data_get($customerPayload, 'address_line_3'),
                    'city_name'            => data_get($customerPayload, 'city_name'),
                    'postal_zone'          => data_get($customerPayload, 'postal_zone'),
                    'country_subentity_code'=> data_get($customerPayload, 'state_code'),
                    'country_code'         => data_get($customerPayload, 'country_code', 'MYS'),
                    'updated_at'           => now(),
                ]);
    
            $customerStatus = 'updated';
    
        } else {
    
            // ================= CREATE CUSTOMER =================
            $customerId = DB::table('customer')->insertGetId([
                'id_developer'         => $client->id_developer,
                'connection_integrate' => $connCode,
                'customer_type'        => 'CUSTOMER',
                'tin_no'               => data_get($customerPayload, 'tin_no'),
                'unique_id'            => strtoupper(Str::random(12)),
                'registration_name'    => data_get($customerPayload, 'registration_name'),
                'identification_no'    => $identification_no,
                'identification_type'  => $identification_type,
                'phone'                => data_get($customerPayload, 'phone'),
                'email'                => data_get($customerPayload, 'email'),
                'address_line_1'       => data_get($customerPayload, 'address_line_1'),
                'address_line_2'       => data_get($customerPayload, 'address_line_2'),
                'address_line_3'       => data_get($customerPayload, 'address_line_3'),
                'city_name'            => data_get($customerPayload, 'city_name'),
                'postal_zone'          => data_get($customerPayload, 'postal_zone'),
                'country_subentity_code'=> data_get($customerPayload, 'state_code'),
                'country_code'         => data_get($customerPayload, 'country_code', 'MYS'),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
    
            $customer = DB::table('customer')->where('id_customer', $customerId)->first();
            $customerStatus = 'created';
        }
    
        // =====================================================
        // 4. BASIC INVOICE DATA
        // =====================================================
        $invoiceNo = data_get($payload, 'invoice_no');
        $saleId    = (int) data_get($payload, 'sale_id_integrate');
        $items     = data_get($payload, 'items', []);
    
        if (!$invoiceNo || !$saleId || empty($items)) {
            return response()->json([
                'status' => 'error',
                'message' => 'invoice_no, sale_id_integrate & items are required'
            ], 400);
        }
    
        $amountBefore = (float) data_get($payload, 'total_amount', 0);
        $issueDate    = now();
    
        DB::beginTransaction();
    

    
            // =================================================
            // 5. FIND EXISTING INVOICE
            // =================================================
            $existingInvoice = DB::table('invoice')
                ->where('connection_integrate', $connCode)
                ->where('sale_id_integrate', $saleId)
                ->first();
    
            if (!empty($existingInvoice) &&  !empty($existingInvoice->long_id)){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invoice already sent to LHDN, If you want to make adjustment Please Do Credit Note, Debit Note Or Refund'
                    ], 400);
            }
// ================================
// 1. UPSERT INVOICE (BASIC DATA)
// ================================
if ($existingInvoice) {

    DB::table('invoice')
        ->where('id_invoice', $existingInvoice->id_invoice)
        ->update([
            'invoice_no'            => $invoiceNo,
            'id_customer'           => $customer->id_customer,
            'id_supplier'           => $supplier->id_customer ?? null,
            'payment_note_term'     => data_get($payload, 'payment_note_term', 'CASH'),
            'total_price_discount'  => data_get($payload, 'total_price_discount'),
            'payment_method'        => data_get($payload, 'payment_method', 'Cash'),
            'tax_category_id'       => '01',
            'tax_scheme_id'         => 'OTH',
            'updated_at'            => now(),
        ]);

    $invoiceId = $existingInvoice->id_invoice;
    $uniqueId  = $existingInvoice->unique_id;

} else {

    $uniqueId = sha1($connCode.$saleId.json_encode($payload));

    $invoiceId = DB::table('invoice')->insertGetId([
        'invoice_no'            => $invoiceNo,
        'unique_id'             => $uniqueId,
        'sale_id_integrate'     => $saleId,
        'connection_integrate'  => $connCode,
        'id_developer'          => $client->id_developer,
        'id_customer'           => $customer->id_customer,
        'id_supplier'           => $supplier->id_customer ?? null,
        'total_price_discount'  => data_get($payload, 'total_price_discount'),
        'invoice_status'        => 'Valid',
        'invoice_type_code'     => '01',
        'tax_category_id'       => '01',
        'tax_scheme_id'         => 'OTH',
        'issue_date'            => $issueDate,
        'payment_note_term'     => data_get($payload, 'payment_note_term', 'CASH'),
        'payment_method'        => data_get($payload, 'payment_method', 'Cash'),
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);
}

// ================================
// 2. UPSERT ITEMS (FROM PAYLOAD)
// ================================
foreach ($items as $index => $it) {

    $itemId = data_get($it, 'item_id');

    $existingItem = DB::table('invoice_item')
        ->where('connection_integrate', $connCode)
        ->where('sale_id_integrate', $saleId)
        ->where('item_id_integrate', $itemId)
        ->first();

    $row = [
        'id_invoice'             => $invoiceId,
        'unique_id'              => $uniqueId,
        'sale_id_integrate'      => $saleId,
        'connection_integrate'   => $connCode,
        'id_developer'           => $client->id_developer,
        'id_customer'            => $customer->id_customer,

        'line_id'                => data_get($it, 'sorting_id', $index + 1),
        'invoiced_quantity'      => data_get($it, 'invoiced_quantity', 0),
        'line_extension_amount'  => data_get($it, 'total_before_tax', 0),
        'item_description'       => data_get($it, 'item_description'),
        'price_amount'           => data_get($it, 'unit_price', 0),
        'price_discount'         => data_get($it, 'price_discount', 0),
        'price_extension_amount' => data_get($it, 'total_before_tax', 0)- data_get($it, 'price_discount', 0),
        'tax'                    => data_get($it, 'tax_amount', 0),
        'item_clasification_value'=> $item_clasification_code ,
        'updated_at'             => now(),
    ];

    if ($existingItem) {
        DB::table('invoice_item')
            ->where('id_invoice_item', $existingItem->id_invoice_item)
            ->update($row);
    } else {
        $row['item_id_integrate'] = $itemId;
        $row['created_at'] = now();
        DB::table('invoice_item')->insert($row);
    }
}

// ================================
// 3. AUTO KIRA TOTAL DARI ITEM
// ================================
$taxPercent = data_get($payload, 'tax_percent', 6);

$totals = DB::table('invoice_item')
    ->where('id_invoice', $invoiceId)
    ->selectRaw('
        SUM(line_extension_amount) as total_before_tax,
        SUM(price_discount) as price_discount,
        SUM(tax) as total_tax_amount
    ')
    ->first();


$totalBeforeTax     = $totals->total_before_tax?? 0;


// versi betul accounting
$taxableAmount      = $totalBeforeTax ;

// kalau item tax tak dihantar, baru kira:

$totalTaxAmount = $totals->total_tax_amount;

$totalAmount = round($taxableAmount + $totalTaxAmount, 2);

// ================================
// 4. UPDATE INVOICE DENGAN TOTAL AUTO
// ================================

DB::table('invoice')
    ->where('id_invoice', $invoiceId)
    ->update([
        'price'                => $totals->total_before_tax,
        'total_price_discount' => $totals->price_discount,
        'taxable_amount'       => $totalBeforeTax-$totals->price_discount,
        'tax_amount'           => $totalTaxAmount,
        'updated_at'           => now(),
    ]);

            DB::commit();
    
            
        // =====================================================
        // 7. SUBMIT MyInvois
        // =====================================================
        $session= session([
            'connection_integrate' =>$connCode,
            'invoice_type_code' => '01',
            'invoice_unique_id' => $uniqueId
        ]);

        Session::put('connection_integrate', $connCode);

        $model = new \App\Models\eInvoisModel($connCode);
    
    

        
    
    
        if( $isAutoToLHDN ==1){
            $result = $model->submit($invoiceId);
            $qr_lhdn = url('/qr_link/'.$uniqueId);

        }else{
            $qr_lhdn='No LHDN QR Link Provided';

            $result = "Please manualy submit in system, since isAutoLHDN = 0";
        }
        
    
        // =====================================================
        // 8. RESPONSE
        // =====================================================
        return response()->json([
            'status'          => 'ok',
            'invoice_id'      => $invoiceId,
            'mysynctax_uuid'  => $uniqueId,
            'customer_status' => $customerStatus,
            'customer_id'     => $customer->id_customer,
            'qr_lhdn'         => $qr_lhdn,
            'result'          => $result
        ], 201);

    


    }

    public function processInvoice(array $payload, string $type)
    {
        DB::beginTransaction();
    
        try {
    
            if (!is_array($payload)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid json'
                ], 400);
                exit();
            }
    
            /* =====================================================
               0. TIN RULE + CLASSIFICATION
            ===================================================== */
            $tin = data_get($payload, 'customer.tin_no');
    
            $item_clasification_code = '022';
            $identification_no = null;
            $identification_type = null;
    
            if ($type == 'GENERAL') {
    
                if ($tin === 'EI00000000010') {
                    $item_clasification_code = '004';
                    $identification_no = 'NA';
                    $identification_type = 'BRN';
    
                } elseif ($tin === 'EI00000000020') {
                    $item_clasification_code = '022';
                    $identification_no = 'NA';
                    $identification_type = 'BRN';
    
                } elseif ($tin === 'EI00000000030') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'LHDN :This TIN No. is only valid for selfbill supplier'
                    ], 400);
                    exit();
    
                } elseif ($tin === 'EI00000000040') {
                    $item_clasification_code = '022';
                    $identification_no = 'NA';
                    $identification_type = 'BRN';
                }
    
            } else {
                $identification_no   = data_get($payload, 'customer.identification_no');
                $identification_type = data_get($payload, 'customer.identification_type');
            }
            
   
            /* =====================================================
               1. AUTH
            ===================================================== */
            $apiKey    = data_get($payload, 'mysynctax_key');
            $apiSecret = data_get($payload, 'mysynctax_secret');
    
            if (!$apiKey || !$apiSecret) {
            
                return response()->json([
                    'status' => 'error',
                    'message' => 'mysynctax_key and mysynctax_secret are required'
                ], 400);
                exit();
            }
    
            $client = DB::table('connection_integrate')
                ->where('mysynctax_key', $apiKey)
                ->where('mysynctax_secret', $apiSecret)
                ->first();
    
            if (!$client) {
               
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid MySyncTax credentials'
                ], 400);
                exit();
            }
    
            $connCode = $client->code;
    
            /* =====================================================
               2. CUSTOMER UPSERT
            ===================================================== */
            $customerPayload = data_get($payload, 'customer');
    

            if (!data_get($customerPayload, 'tin_no')) {
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'customer.tin_no is required'
                ], 400);
                exit();
            }

            if(data_get($customerPayload, 'country_code')==''){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Country code is required'
                ], 400);
                exit();
                }

            if(data_get($customerPayload, 'state_code')==''){
                return response()->json([
                    'status' => 'error',
                    'message' => 'State code is required'
                ], 400);
                exit();
                }
    
    
            if(data_get($customerPayload, 'phone')==''){
            return response()->json([
                'status' => 'error',
                'message' => 'Phone No. is required'
            ], 400);
            exit();
            }

            if(data_get($customerPayload, 'email')==''){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email is is required'
                ], 400);
                exit();
                }

            if ($type == 'NORMAL') {
            $customer = DB::table('customer')
                ->where('connection_integrate', $connCode)
                ->where('tin_no', data_get($customerPayload, 'tin_no'))
                ->where('customer_type', 'CUSTOMER')
                ->first();
       
            $supplier = DB::table('customer')
                ->where('connection_integrate', $connCode)
                ->where('customer_type', 'SUPPLIER')
                ->first();
    
            if ($customer) {
    
                DB::table('customer')->where('id_customer', $customer->id_customer)->update([
                    'registration_name' => data_get($customerPayload, 'registration_name'),
                    'identification_no' => $identification_no,
                    'identification_type' => $identification_type,
                    'phone' => data_get($customerPayload, 'phone'),
                    'email' => data_get($customerPayload, 'email'),
                    'address_line_1' => data_get($customerPayload, 'address_line_1'),
                    'address_line_2' => data_get($customerPayload, 'address_line_2'),
                    'address_line_3' => data_get($customerPayload, 'address_line_3'),
                    'city_name' => data_get($customerPayload, 'city_name'),
                    'postal_zone' => data_get($customerPayload, 'postal_zone'),
                    'country_subentity_code' => data_get($customerPayload, 'state_code'),
                    'country_code' => data_get($customerPayload, 'country_code', 'MYS'),
                    'updated_at' => now(),
                ]);
    
                $customerStatus = 'updated';
    
            } else {
    
                $customerId = DB::table('customer')->insertGetId([
                    'id_developer' => $client->id_developer,
                    'connection_integrate' => $connCode,
                    'customer_type' => 'CUSTOMER',
                    'tin_no' => data_get($customerPayload, 'tin_no'),
                    'unique_id' => strtoupper(Str::random(12)),
                    'registration_name' => data_get($customerPayload, 'registration_name'),
                    'identification_no' => $identification_no,
                    'identification_type' => $identification_type,
                    'phone' => data_get($customerPayload, 'phone'),
                    'email' => data_get($customerPayload, 'email'),
                    'address_line_1' => data_get($customerPayload, 'address_line_1'),
                    'address_line_2' => data_get($customerPayload, 'address_line_2'),
                    'address_line_3' => data_get($customerPayload, 'address_line_3'),
                    'city_name' => data_get($customerPayload, 'city_name'),
                    'postal_zone' => data_get($customerPayload, 'postal_zone'),
                    'country_subentity_code' => data_get($customerPayload, 'state_code'),
                    'country_code' => data_get($customerPayload, 'country_code', 'MYS'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                $customer = DB::table('customer')->where('id_customer', $customerId)->first();
                $customerStatus = 'created';
            }
        }else{

            $customer = DB::table('customer')
                ->where('connection_integrate', $connCode)
                ->where('tin_no', data_get($customerPayload, 'tin_no'))
                ->where('identification_no', data_get($customerPayload, 'identification_no'))
                ->where('registration_name', data_get($customerPayload, 'registration_name'))
                ->where('customer_type', 'CUSTOMER')
                ->first();
       
            $supplier = DB::table('customer')
                ->where('connection_integrate', $connCode)
                ->where('customer_type', 'SUPPLIER')
                ->first();
    
            if ($customer) {
    
                DB::table('customer')->where('id_customer', $customer->id_customer)->update([
                    'registration_name' => data_get($customerPayload, 'registration_name'),
                    'identification_no' => $identification_no,
                    'identification_type' => $identification_type,
                    'phone' => data_get($customerPayload, 'phone'),
                    'email' => data_get($customerPayload, 'email'),
                    'address_line_1' => data_get($customerPayload, 'address_line_1'),
                    'address_line_2' => data_get($customerPayload, 'address_line_2'),
                    'address_line_3' => data_get($customerPayload, 'address_line_3'),
                    'city_name' => data_get($customerPayload, 'city_name'),
                    'postal_zone' => data_get($customerPayload, 'postal_zone'),
                    'country_subentity_code' => data_get($customerPayload, 'state_code'),
                    'country_code' => data_get($customerPayload, 'country_code', 'MYS'),
                    'updated_at' => now(),
                ]);
    
                $customerStatus = 'updated';
    
            } else {
    
                $customerId = DB::table('customer')->insertGetId([
                    'id_developer' => $client->id_developer,
                    'connection_integrate' => $connCode,
                    'customer_type' => 'CUSTOMER',
                    'tin_no' => data_get($customerPayload, 'tin_no'),
                    'unique_id' => strtoupper(Str::random(12)),
                    'registration_name' => data_get($customerPayload, 'registration_name'),
                    'identification_no' => $identification_no,
                    'identification_type' => $identification_type,
                    'phone' => data_get($customerPayload, 'phone'),
                    'email' => data_get($customerPayload, 'email'),
                    'address_line_1' => data_get($customerPayload, 'address_line_1'),
                    'address_line_2' => data_get($customerPayload, 'address_line_2'),
                    'address_line_3' => data_get($customerPayload, 'address_line_3'),
                    'city_name' => data_get($customerPayload, 'city_name'),
                    'postal_zone' => data_get($customerPayload, 'postal_zone'),
                    'country_subentity_code' => data_get($customerPayload, 'state_code'),
                    'country_code' => data_get($customerPayload, 'country_code', 'MYS'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
    
                $customer = DB::table('customer')->where('id_customer', $customerId)->first();
                $customerStatus = 'created';
            }

        }
            /* =====================================================
               3. BASIC INVOICE
            ===================================================== */
            $invoiceNo = data_get($payload, 'invoice_no');
            $saleId    = (int) data_get($payload, 'sale_id_integrate');
            $items     = data_get($payload, 'items', []);
    
            if (!$invoiceNo || !$saleId || empty($items)) {
               
                return response()->json([
                    'status' => 'error',
                    'message' => 'invoice_no, sale_id_integrate & items are required'
                ], 400);
                exit();
                
            }
    
            $existingInvoice = DB::table('invoice')
                ->where('connection_integrate', $connCode)
                ->where('sale_id_integrate', $saleId)
                ->first();
    
            if ($existingInvoice && !empty($existingInvoice->long_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice already sent to LHDN. Please use Credit/Debit/Refund Note'
                ], 400);
                exit();
               
            }
    
            if ($existingInvoice) {
    
                $invoiceId = $existingInvoice->id_invoice;
                $uniqueId  = $existingInvoice->unique_id;
    
            } else {
    
                $uniqueId = sha1($connCode.$saleId.json_encode($payload));
    
                $invoiceId = DB::table('invoice')->insertGetId([
                    'invoice_no' => $invoiceNo,
                    'unique_id' => $uniqueId,
                    'sale_id_integrate' => $saleId,
                    'connection_integrate' => $connCode,
                    'id_developer' => $client->id_developer,
                    'id_customer' => $customer->id_customer,
                    'id_supplier' => $supplier->id_customer ?? null,
                    'invoice_status' => 'Valid',
                    'invoice_type_code' => '01',
                    'tax_category_id' => '01',
                    'tax_scheme_id' => 'OTH',
                    'issue_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
    
            /* =====================================================
               4. ITEMS
            ===================================================== */
            foreach ($items as $index => $it) {
                $line_extension_amount=data_get($it, 'unit_price', 0)*data_get($it, 'invoiced_quantity', 0);

                DB::table('invoice_item')->updateOrInsert(
                    [
                        'connection_integrate' => $connCode,
                        'sale_id_integrate' => $saleId,
                        'item_id_integrate' => data_get($it, 'item_id')
                    ],
                    [
                        'id_invoice' => $invoiceId,
                        'unique_id' => $uniqueId,
                        'id_developer' => $client->id_developer,
                        'id_customer' => $customer->id_customer,
                        'line_id' => data_get($it, 'sorting_id', $index + 1),
                        'invoiced_quantity' => data_get($it, 'invoiced_quantity', 0),
                        'line_extension_amount'  => $line_extension_amount,
                        'item_description' => data_get($it, 'item_description'),
                        'price_amount' => data_get($it, 'unit_price', 0),
                        'price_discount' => data_get($it, 'price_discount', 0),
                        'price_extension_amount' => $line_extension_amount- data_get($it, 'price_discount', 0),
                        'tax' => data_get($it, 'tax_amount', 0),
                        'item_clasification_value' => $item_clasification_code,
                        'updated_at' => now(),
                    ]
                );
            }
    
            /* =====================================================
               5. TOTAL RECALC
            ===================================================== */
            $totals = DB::table('invoice_item')
                ->where('id_invoice', $invoiceId)
                ->selectRaw('SUM(line_extension_amount) total_before_tax, SUM(price_discount) price_discount, SUM(tax) total_tax')
                ->first();
    
            DB::table('invoice')->where('id_invoice', $invoiceId)->update([
                'price' => $totals->total_before_tax,
                'total_price_discount' => $totals->price_discount,
                'taxable_amount' => $totals->total_before_tax - $totals->price_discount,
                'tax_amount' => $totals->total_tax,
                'updated_at' => now(),
            ]);
    
            DB::commit();
       // =====================================================
        // 7. SUBMIT MyInvois
        // =====================================================
        $session= session([
            'connection_integrate' =>$connCode,
            'invoice_type_code' => '01',
            'invoice_unique_id' => $uniqueId
        ]);

        Session::put('connection_integrate', $connCode);

        $model = new eInvoisModel($connCode);
    
        $isAutoToLHDN=data_get($payload, 'isAutoToLHDN');

        if( $isAutoToLHDN ==1){
            $result = $model->submit($invoiceId);
            $qr_lhdn = url('/qr_link/'.$uniqueId);

        }else{
            $qr_lhdn='No LHDN QR Link Provided';

            $result = "Please manualy submit in system, since isAutoLHDN = 0";
        }
        
    
        // =====================================================
        // 8. RESPONSE
        // =====================================================
        return response()->json([
            'status'          => 'ok',
            'invoice_id'      => $invoiceId,
            'mysynctax_uuid'  => $uniqueId,
            'customer_status' => $customerStatus,
            'customer_id'     => $customer->id_customer,
            'qr_lhdn'         => $qr_lhdn,
            'result'          => $result
        ], 201);
    
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
          
        }
    }
    


  public function processNote(array $payload, $mode = 'normal')
{
    DB::beginTransaction();

    try {

        if (!is_array($payload)) {
           
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON payload'
            ], 400);
            exit();
        }

        /* =====================================================
           1. AUTH
        ===================================================== */
        $apiKey    = data_get($payload, 'mysynctax_key');
        $apiSecret = data_get($payload, 'mysynctax_secret');

        if (!$apiKey || !$apiSecret) {
          
            return response()->json([
                'status' => 'error',
                'message' => 'mysynctax_key and mysynctax_secret are required'
            ], 400);
            exit();
        }

        $client = DB::table('connection_integrate')
            ->where('mysynctax_key', $apiKey)
            ->where('mysynctax_secret', $apiSecret)
            ->first();

        if (!$client) {
            
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid MySyncTax credentials'
            ], 400);
            exit();
        }

        $connCode = $client->code;

        /* =====================================================
           2. NOTE TYPE MAP
        ===================================================== */
        $noteType = data_get($payload, 'note_type');

        $typeMap = [
            'normal' => [
                'credit' => ['code' => '02', 'sign' => -1, 'label' => 'Credit'],
                'debit'  => ['code' => '03', 'sign' =>  1, 'label' => 'Debit'],
                'refund' => ['code' => '04', 'sign' => -1, 'label' => 'Refund'],
            ],
            'selfbill' => [
                'credit' => ['code' => '12', 'sign' => -1, 'label' => 'Credit'],
                'debit'  => ['code' => '13', 'sign' =>  1, 'label' => 'Debit'],
                'refund' => ['code' => '14', 'sign' => -1, 'label' => 'Refund'],
            ],
            'general' => [
                'credit' => ['code' => '02', 'sign' => -1, 'label' => 'Credit'],
                'debit'  => ['code' => '03', 'sign' =>  1, 'label' => 'Debit'],
                'refund' => ['code' => '04', 'sign' => -1, 'label' => 'Refund'],
            ],
            'selfbill_general' => [
                'credit' => ['code' => '12', 'sign' => -1, 'label' => 'Credit'],
                'debit'  => ['code' => '13', 'sign' =>  1, 'label' => 'Debit'],
                'refund' => ['code' => '14', 'sign' => -1, 'label' => 'Refund'],
            ]
        ];

        if (!isset($typeMap[$mode][$noteType])) {
           
            return response()->json([
                'status' => 'error',
                'message' => "Invalid note_type for mode {$mode}"
            ], 400);
            exit();
        }

        $map = $typeMap[$mode][$noteType];
        $invoiceTypeCode = $map['code'];
        $sign            = $map['sign'];
        $label           = $map['label'];

        /* =====================================================
           3. ORIGINAL INVOICE
        ===================================================== */
        $originalInvoiceId = data_get($payload, 'sale_id_integrate');
        $mysynctax_uuid    = data_get($payload, 'mysynctax_uuid');

        if (!is_numeric($originalInvoiceId) || !$mysynctax_uuid) {
            

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid original invoice reference'
            ], 400);
            exit();
        }

        $original = DB::table('invoice')
            ->where('sale_id_integrate', $originalInvoiceId)
            ->where('unique_id', $mysynctax_uuid)
            ->first();

        if (!$original) {
   
            return response()->json([
                'status' => 'error',
                'message' => "Original invoice not found"
            ], 400);
            exit();
        }

        $uniqueId = (string) \Illuminate\Support\Str::uuid();

        /* =====================================================
           4. CREATE NOTE INVOICE
        ===================================================== */
        $noteInvoiceId = DB::table('invoice')->insertGetId([
            'unique_id'            => $uniqueId,
            'connection_integrate' => $connCode,
            'sale_id_integrate'    => $originalInvoiceId,
            'id_developer'         => $client->id_developer,
            'invoice_no'           => strtoupper($label).'-NOTE-'.now()->format('YmdHis'),
            'invoice_type_code'    => $invoiceTypeCode,
            'issue_date'           => now(),
            'id_customer'          => $original->id_customer,
            'id_supplier'          => $original->id_supplier,
            'previous_id_invoice'  => $original->id_invoice,
            'previous_invoice_no'  => $original->invoice_no,
            'previous_uuid'        => $original->unique_id,
            'payment_note_term'    => 'CASH',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        /* =====================================================
           5. ITEMS
        ===================================================== */
        $items = data_get($payload, 'items', []);
        if (!is_array($items) || count($items) === 0) {
   
            return response()->json([
                'status' => 'error',
                'message' => "'Items are required"
            ], 400);
            exit();
        }

        foreach ($items as $item) {

            $itemId = data_get($item, 'item_id');

            $oriItem = DB::table('invoice_item')
                ->where('item_id_integrate', $itemId)
                ->where('id_invoice', $original->id_invoice)
                ->first();

            if (!$oriItem) {
              

                return response()->json([
                    'status' => 'error',
                    'message' => "Invalid invoice previous item reference: {$itemId}"
                ], 400);
                exit();
            }

            $qty      = (float) data_get($item, 'qty', 0);
            $price    = (float) data_get($item, 'price', 0);
            $discount = (float) data_get($item, 'discount', 0);
            $tax      = (float) data_get($item, 'tax', 0);

            $lineAmount = (($qty * $price) - $discount) * $sign;

            DB::table('invoice_item')->insert([
                'id_invoice'               => $noteInvoiceId,
                'sale_id_integrate'        => $originalInvoiceId,
                'item_id_integrate'        => $itemId,
                'id_developer'             => $client->id_developer,
                'unique_id'                => $uniqueId,
                'connection_integrate'     => $connCode,
                'previous_id_invoice'      => $original->id_invoice,
                'previous_id_invoice_item' => $oriItem->id_invoice_item,
                'previous_amount'          => $oriItem->line_extension_amount,
                'line_id'                  => $oriItem->line_id,
                'invoiced_quantity'        => $qty,
                'price_amount'             => $price,
                'price_discount'           => $discount,
                'line_extension_amount'    => $lineAmount,
                'price_extension_amount'   => $lineAmount,
                'tax'                      => $tax * $sign,
                'item_description'         => data_get($item, 'description', ''),
                'item_clasification_value' => '022',
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        /* =====================================================
           6. TOTAL
        ===================================================== */
        $total    = DB::table('invoice_item')->where('id_invoice', $noteInvoiceId)->sum('line_extension_amount');
        $taxTotal = DB::table('invoice_item')->where('id_invoice', $noteInvoiceId)->sum('tax');

        DB::table('invoice')->where('id_invoice', $noteInvoiceId)->update([
            'price'          => $total,
            'taxable_amount' => $total,
            'tax_amount'     => $taxTotal,
            'updated_at'     => now(),
        ]);

         /* =====================================================
                7. SUBMIT LHDN
            ===================================================== */

            if(!$original->uuid){
            return response()->json([
                'status' => 'error',
                'message' => "This invoice not submited yet to LHDN"
            ], 400);
            exit();
            }

            session([
                'invoice_unique_id'   => $uniqueId,
                'previous_uuid'       => $original->uuid,
                'previous_invoice_no' => $original->invoice_no,
                'invoice_type_code'   => $invoiceTypeCode,
            ]);

            $model  = new eInvoisModel($connCode);
            $result = $model->submit($noteInvoiceId);

            DB::commit();

            return response()->json([
                'status'         => 'ok',
                'note_type'      => $noteType,
                'invoice_id'     => $noteInvoiceId,
                'mysynctax_uuid' => $uniqueId,
                'qr_lhdn'        => url('/qr_link/'.$uniqueId),
                'result'         => $result
            ], 201);


        

    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}

}
?>
