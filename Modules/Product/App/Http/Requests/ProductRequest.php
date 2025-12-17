<?php

namespace Modules\Product\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'name' => 'required|string',
                'slug' => 'required|string',
                'category_id' => 'required',
                'restaurant_id' => 'required',
                'image' => 'required',
                'product_price' => 'required',
            ];
        }

        if ($this->isMethod('put')) {
            $rules = [
                'name' => 'required|string',
                'slug' => 'required|string',
                'category_id' => 'required',
                'restaurant_id' => 'required',
                'product_price' => 'required',
            ];
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
            'name.string' => trans('translate.Name should be string'),
            'slug.required' => trans('translate.Slug is required'),
            'slug.string' => trans('translate.Slug should be string'),
            'price.required' => trans('translate.Price is required'),
            'product_price.required' => trans('translate.Price is required'),
            'status.required' => trans('translate.Status is required'),
            'category_id.required' => trans('translate.Category is required'),
            'restaurant_id.required' => trans('translate.Restaurant is required'),
            'image.required' => trans('translate.Image is required'),
        ];
    }
}
