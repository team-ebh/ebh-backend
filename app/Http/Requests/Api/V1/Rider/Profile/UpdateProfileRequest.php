<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:60',
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
            'full_name.required' => trans('riders.api.validation.profile.full_name.required'),
            'full_name.string' => trans('riders.api.validation.profile.full_name.string'),
            'full_name.min' => trans('riders.api.validation.profile.full_name.min'),
            'full_name.max' => trans('riders.api.validation.profile.full_name.max'),
            'email.email' => trans('riders.api.validation.profile.email.email'),
            'email.max' => trans('riders.api.validation.profile.email.max'),
            'phone_number.required' => trans('riders.api.validation.profile.phone_number.required'),
            'phone_number.regex' => trans('riders.api.validation.profile.phone_number.regex'),
        ];
    }
}
