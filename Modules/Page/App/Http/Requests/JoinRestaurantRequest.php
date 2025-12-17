<?php

namespace Modules\Page\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinRestaurantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules =  [
            'join_restaurant_title' => 'required',
            'join_restaurant_des' => 'required',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'join_restaurant_title.required' => trans('translate.Title is required'),
            'join_restaurant_des.required' => trans('translate.Description is required'),
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
