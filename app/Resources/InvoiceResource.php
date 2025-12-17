<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_invoice' => $this->id_invoice,
            'invoice_status' => $this->invoice_status,
            'invoice_no' => $this->invoice_no,
            'invoice_type_code' => $this->invoice_type_code,
            'issue_date' => $this->issue_date,
            'price' => $this->price,
            'taxable_amount' => $this->taxable_amount,
            'tax_amount' => $this->tax_amount,
            'tax_category_id' => $this->tax_category_id,
            'tax_exemption_reason' => $this->tax_exemption_reason,
            'tax_scheme_id' => $this->tax_scheme_id,
            'tax_percent' => $this->tax_percent,
            'payment_note_term' => $this->payment_note_term,
            'payment_financial_account' => $this->payment_financial_account,
            'include_signature' => $this->include_signature,
            'uuid' => $this->uuid,
            'submission_uuid' => $this->submission_uuid,
            'long_id' => $this->long_id,
            'payment_method' => $this->payment_method,
            'items' => InvoiceItemResource::collection($this->items),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}