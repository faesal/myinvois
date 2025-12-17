<?php

namespace Modules\Coupon\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {


        if ($this->isMethod('post')) {
            return [
                'name' => 'required',
                'code' => 'required|unique:coupons',
                'expired_date' => 'required',
                'min_purchase_price' => 'required|numeric',
                'discount_amount' => 'required|numeric',
                'discount_type' => 'required',
            ];
        }

        if ($this->isMethod('put')) {
            return [
                'name' => 'required',
                'code' => 'required|unique:coupons,code,'.$this->coupon.',id',
                'expired_date' => 'required',
                'min_purchase_price' => 'required|numeric',
                'discount_amount' => 'required|numeric',
                'discount_type' => 'required',
            ];
        }



    }


    public function messages(): array
    {
        return [
            'name.required' => trans('translate.Name is required'),
            'code.required' => trans('translate.Code is required'),
            'code.unique' => trans('translate.Code already exist'),
            'expired_date.required' => trans('translate.Expired date is required'),
            'min_purchase_price.required' => trans('translate.Min purchase price is required'),
            'discount_amount.required' => trans('translate.Amount is required'),
            'discount_type.required' => trans('translate.Discount type is required'),
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
