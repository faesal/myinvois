<?php

namespace Modules\Addon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name'=>'required',
                'price'=>'required',
                'restaurant_id' => 'required'
            ];
        }

        if ($this->isMethod('put')) {

            if($this->request->get('lang_code') == admin_lang()){
                $rules = [
                    'name'=>'required',
                    'price'=>'required',
                    'restaurant_id'=> 'required'
                ];
            }else{
                $rules = [
                    'name'=>'required',
                ];
            }
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('translate.Name is required'),
            'name.unique' => trans('translate.Name already exist'),
            'price.required' => trans('translate.Price is required'),
            'restaurant_id.required' => trans('translate.Restaurant is required'),
        ];
    }
}
