<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\Invoice;
use App\Libraries\MyInvois\Profile\DocumentProfile;

class BillingReferenceBuilder
{
    public function build(Invoice $invoice, DocumentProfile $profile): ?array
    {
        if (!$profile->requireBillingReference()) {
            return null;
        }

        return [
            'BillingReference' => [[
                'InvoiceDocumentReference' => [[
                    'ID' => [[ '_' => $invoice->previous_invoice_no ]],
                    'UUID' => [[ '_' => $invoice->previous_uuid ]]
                ]]
            ]]
        ];
    }
}
