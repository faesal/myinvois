<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_invoice_item' => $this->id_invoice_item,
            'id_customer' => $this->id_customer,
            'id_invoice' => $this->id_invoice,
            'line_id' => $this->line_id,
            'invoiced_quantity' => $this->invoiced_quantity,
            'line_extension_amount' => $this->line_extension_amount,
            'item_description' => $this->item_description,
            'price_amount' => $this->price_amount,
            'price_discount' => $this->price_discount,
            'price_extension_amount' => $this->price_extension_amount,
            'item_clasification_type' => $this->item_clasification_type,
            'item_clasification_value' => $this->item_clasification_value,
        ];
    }
}