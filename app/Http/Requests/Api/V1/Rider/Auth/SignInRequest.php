<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /**
             * @example 65656565
             */
            'phone_number' => [
                'required',
                'regex:/^[0-9]{8}$/',
                'exists:riders,phone_number',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => trans('customers.api.auth.phone_number.required'),
            'phone_number.regex' => trans('customers.api.auth.phone_number.regex'),
            'phone_number.exists' => trans('customers.api.auth.phone_number.not_found'),
        ];
    }
}
