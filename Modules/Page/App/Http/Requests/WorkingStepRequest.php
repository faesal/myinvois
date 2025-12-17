<?php

namespace Modules\Page\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkingStepRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $rules =  [
            'working_step_title1' => 'required',
            'working_step_des1' => 'required',

            'working_step_title2' => 'required',
            'working_step_des2' => 'required',

            'working_step_title3' => 'required',
            'working_step_des3' => 'required',

            'working_step_title4' => 'required',
            'working_step_des4' => 'required',

        ];

        return $rules;
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

    public function messages(): array
    {
        return [
            'working_step_title1.required' => trans('translate.Title is required'),
            'working_step_des1.required' => trans('translate.Description is required'),

            'working_step_title2.required' => trans('translate.Title is required'),
            'working_step_des2.required' => trans('translate.Description is required'),

            'working_step_title3.required' => trans('translate.Title is required'),
            'working_step_des3.required' => trans('translate.Description is required'),

            'working_step_title4.required' => trans('translate.Title is required'),
            'working_step_des4.required' => trans('translate.Description is required'),
        ];
    }
}
