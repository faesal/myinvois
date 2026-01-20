<?php
namespace App\Libraries\MyInvois\Builder;

use App\Models\InvoiceItem;

class LineBuilder
{
    public function build(InvoiceItem $item): array
    {
        return [
            'ID' => [[ '_' => $item->line_id ]],
            'InvoicedQuantity' => [[ '_' => $item->invoiced_quantity, 'unitCode' => 'C62' ]],
            'LineExtensionAmount' => [[ '_' => $item->line_extension_amount, 'currencyID' => 'MYR' ]],
            'Item' => [[
                'Description' => [[ '_' => $item->item_description ]]
            ]],
            'Price' => [[
                'PriceAmount' => [[ '_' => $item->price_amount, 'currencyID' => 'MYR' ]]
            ]]
        ];
    }
}
