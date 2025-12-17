<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id_customer' => $this->id_customer,
            'customer_type' => $this->customer_type,
            'tin_no' => $this->tin_no,
            'registration_name' => $this->registration_name,
            'identification_no' => $this->identification_no,
            'identification_type' => $this->identification_type,
            'sst_registration' => $this->sst_registration,
            'phone' => $this->phone,
            'email' => $this->email,
            'city_name' => $this->city_name,
            'postal_zone' => $this->postal_zone,
            'country_subentity_code' => $this->country_subentity_code,
            'country_code' => $this->country_code,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'address_line_3' => $this->address_line_3,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}