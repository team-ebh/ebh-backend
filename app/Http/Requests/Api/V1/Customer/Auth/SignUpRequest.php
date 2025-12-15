<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:30',
            ],
            'last_name' => [
                'required',
                'string',
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
            'first_name.max' => trans('customers.api.auth.first_name.max'),
            'last_name.required' => trans('customers.api.auth.last_name.required'),
            'last_name.string' => trans('customers.api.auth.last_name.string'),
            'last_name.max' => trans('customers.api.auth.last_name.max'),
            'email.email' => trans('customers.api.auth.email.email'),
            'email.max' => trans('customers.api.auth.email.max'),
            'phone_number.required' => trans('customers.api.auth.phone_number.required'),
            'phone_number.regex' => trans('customers.api.auth.phone_number.regex'),
        ];
    }
}
