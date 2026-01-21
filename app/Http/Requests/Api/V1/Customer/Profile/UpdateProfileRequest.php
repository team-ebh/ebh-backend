<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:30',
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:30',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            /**
             * @example 65656565
             */
            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{8}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => trans('customers.api.auth.first_name.required'),
            'first_name.string' => trans('customers.api.auth.first_name.string'),
            'first_name.min' => trans('customers.api.auth.first_name.min'),
            'first_name.max' => trans('customers.api.auth.first_name.max'),
            'last_name.required' => trans('customers.api.auth.last_name.required'),
            'last_name.string' => trans('customers.api.auth.last_name.string'),
            'last_name.min' => trans('customers.api.auth.last_name.min'),
            'last_name.max' => trans('customers.api.auth.last_name.max'),
            'email.email' => trans('customers.api.auth.email.email'),
            'email.max' => trans('customers.api.auth.email.max'),
            'phone_number.required' => trans('customers.api.auth.phone_number.required'),
            'phone_number.regex' => trans('customers.api.auth.phone_number.regex'),
        ];
    }
}
