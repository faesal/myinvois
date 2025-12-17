<?php

namespace App\Services;

use Klsheng\Myinvois\MyInvoisClient;

class MyInvoisService
{
    protected $client;

    public function __construct()
    {
        $this->client = new MyInvoisClient(
            env('MYINVOIS_CLIENT_ID'),
            env('MYINVOIS_CLIENT_SECRET'),
            env('MYINVOIS_SANDBOX', true)
        );
        $this->client->login();
    }

    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    // Anda boleh tambah method lain seperti createInvoice, getInvoiceStatus dsb.
}
