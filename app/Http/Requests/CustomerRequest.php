<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_type' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'registration_name' => 'required|string|max:255',
            'identification_no' => 'nullable|string|max:255',
            'identification_type' => 'nullable|string|max:255',
            'sst_registration' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'city_name' => 'nullable|string|max:100',
            'postal_zone' => 'nullable|string|max:20',
            'country_subentity_code' => 'nullable|string|max:10',
            'country_code' => 'nullable|string|max:3',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address_line_3' => 'nullable|string|max:255',
        ];
    }
}