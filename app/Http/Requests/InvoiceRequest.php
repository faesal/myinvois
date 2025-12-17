<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'invoice_status' => 'required|string|max:20',
            'invoice_no' => 'required|string|max:20',
            'invoice_type_code' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'price' => 'required|string',
            'taxable_amount' => 'required|string',
            'tax_amount' => 'required|string',
            'tax_category_id' => 'required|string',
            'tax_scheme_id' => 'required|string',
            'tax_percent' => 'required|string',
            'payment_note_term' => 'required|string',
            'payment_financial_account' => 'required|string',
            'include_signature' => 'boolean',
            'payment_method' => 'nullable|string',
            
            'items' => 'required|array',
            'items.*.id_customer' => 'required|integer',
            'items.*.line_id' => 'required|string|max:50',
            'items.*.invoiced_quantity' => 'required|numeric',
            'items.*.line_extension_amount' => 'required|numeric',
            'items.*.item_description' => 'required|string',
            'items.*.price_amount' => 'required|numeric',
            'items.*.price_discount' => 'required|numeric',
            'items.*.price_extension_amount' => 'nullable|numeric',
            'items.*.item_clasification_type' => 'nullable|numeric',
            'items.*.item_clasification_value' => 'nullable|numeric'
        ];
    }
}