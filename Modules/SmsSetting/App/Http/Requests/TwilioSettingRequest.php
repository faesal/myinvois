<?php

namespace Modules\SmsSetting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwilioSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'twilio_sid' => 'required',
            'twilio_auth_token' => 'required',
            'default_phone_code' => 'required',
            'twilio_phone_number' => 'required',
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
