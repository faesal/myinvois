<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\Invoice;

class TaxBuilder
{
    public function build(Invoice $invoice): array
    {
        return [
            'TaxAmount' => [[ '_' => $invoice->tax_amount, 'currencyID' => 'MYR' ]]
        ];
    }
}
