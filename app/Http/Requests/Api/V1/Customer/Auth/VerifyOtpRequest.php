<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            ],
            /**
             * On the dev and stage servers, use the code 0421 to pass the OTP
             *
             * @example 0421
             */
            'otp' => [
                'required',
                'string',
                'size:4',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => trans('customers.api.auth.phone_number.required'),
            'phone_number.regex' => trans('customers.api.auth.phone_number.regex'),
            'otp.required' => trans('customers.api.auth.otp.required'),
            'otp.string' => trans('customers.api.auth.otp.string'),
            'otp.size' => trans('customers.api.auth.otp.size'),
        ];
    }
}
