<?php

namespace Modules\SmsSetting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BiztechSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'biztech_api_key' => 'required',
            'biztech_client_id' => 'required',
            'biztech_sender_id' => 'required',
            'default_phone_code' => 'required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
