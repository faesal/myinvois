<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\Invoice;

class MonetaryBuilder
{
    public function build(Invoice $invoice): array
    {
        return [
            'PayableAmount' => [[ '_' => $invoice->price, 'currencyID' => 'MYR' ]]
        ];
    }
}
