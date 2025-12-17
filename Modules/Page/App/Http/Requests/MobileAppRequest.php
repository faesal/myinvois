<?php

namespace Modules\Page\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileAppRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $rules =  [
            'mobile_app_title' => 'required',
            'mobile_app_des' => 'required',
        ];

        if($this->request->get('lang_code') == admin_lang()){
            $rules['mobile_playstore'] = 'required';
            $rules['mobile_appstore'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'mobile_app_title.required' => trans('translate.Title is required'),
            'mobile_app_des.required' => trans('translate.Description is required'),
            'mobile_playstore.required' => trans('translate.Play store is required'),
            'mobile_appstore.required' => trans('translate.App store is required'),
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
