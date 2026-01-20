<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\Invoice;
use App\Libraries\MyInvois\Profile\DocumentProfile;

class HeaderBuilder
{
    public function build(Invoice $invoice, DocumentProfile $profile): array
    {
        return [
            'ID' => [[ '_' => $invoice->invoice_no ]],
            'IssueDate' => [[ '_' => $invoice->issue_date->format('Y-m-d') ]],
            'IssueTime' => [[ '_' => $invoice->issue_date->format('H:i:s\Z') ]],
            'InvoiceTypeCode' => [[
                '_' => $profile->code(),
                'listVersionID' => '1.0'
            ]],
            'DocumentCurrencyCode' => [[ '_' => 'MYR' ]]
        ];
    }
}
