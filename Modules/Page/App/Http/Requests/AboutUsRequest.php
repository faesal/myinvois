<?php

namespace Modules\Page\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AboutUsRequest extends FormRequest
{
    public function rules()
    {
        $rules =  [
            'title' => 'required',
            'description' => 'required',
            'customer_title' => 'required',
            'customer_des' => 'required',
            'branch_title' => 'required',
            'branch_des' => 'required',
        ];

        if($this->request->get('lang_code') == admin_lang()){
            $rules['experience_year'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => trans('translate.Title is required'),
            'description.required' => trans('translate.Description is required'),
            'customer_title.required' => trans('translate.Title is required'),
            'customer_des.required' => trans('translate.Description is required'),
            'branch_title.required' =>trans('translate.Title is required'),
            'branch_des.required' => trans('translate.Description is required'),
            'experience_year.required' => trans('translate.Year is required'),
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
