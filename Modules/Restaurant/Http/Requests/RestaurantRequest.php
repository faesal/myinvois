<?php

namespace Modules\Restaurant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestaurantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'restaurant_name' => 'required|max:255',
            'city_id' => 'required',
            'cuisines' => 'required',
            'whatsapp' => 'required|max:255',
            'address' => 'required|max:255',
            'latitude' => 'required',
            'longitude' => 'required',
            'max_delivery_distance' => 'required|numeric',
            'owner_name' => 'required|max:255',
            'owner_email' => 'required|max:255',
            'owner_phone' => 'required|max:255',
            'name' => 'required|max:255',
            'opening_hour' => 'required|max:255',
            'closing_hour' => 'required|max:255',
            'min_processing_time' => 'required|max:255|numeric',
            'max_processing_time' => 'required|max:255|numeric',
            'time_slot_separate' => 'required|max:255|numeric',
        ];

        if ($this->isMethod('post')) {
            $rules = [
                'slug' => 'required|unique:restaurants|max:255',
                'logo' => 'required|image|mimes:jpeg,jpg,png',
                'cover_image' => 'required|image|mimes:jpeg,jpg,png,webp',
                'email' => 'required|max:255|email|unique:restaurants',
                'password' => 'required|max:255|min:4',
            ];

        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'restaurant_name.required' => trans('translate.Restaurant name is required'),
            'slug.required' => trans('translate.Slug is required'),
            'slug.unique' => trans('translate.Slug already exist'),
            'city_id.required' => trans('translate.City is required'),
            'cuisines.required' => trans('translate.Cuisine is required'),
            'logo.required' => trans('translate.Logo is required'),
            'whatsapp.required' => trans('translate.Whatsapp is required'),
            'address.required' => trans('translate.Address is required'),
            'latitude.required' => trans('translate.Latitude is required'),
            'longitude.required' => trans('translate.Longitude is required'),
            'max_delivery_distance.required' => trans('translate.Maximum delivery distance is required'),
            'owner_name.required' => trans('translate.Owner name is required'),
            'owner_email.required' => trans('translate.Owner email is required'),
            'owner_phone.required' => trans('translate.Owner phone is required'),
            'name.required' => trans('translate.Name is required'),
            'email.required' => trans('translate.Email is required'),
            'password.required' => trans('translate.Password is required'),
            'opening_hour.required' => trans('translate.Openning hour is required'),
            'closing_hour.required' => trans('translate.Closing hour is required'),
            'min_processing_time.required' => trans('translate.Minimum processing time is required'),
            'max_processing_time.required' => trans('translate.Maximum processing time is required'),
            'time_slot_separate.required' => trans('translate.Time slot separate is required'),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
