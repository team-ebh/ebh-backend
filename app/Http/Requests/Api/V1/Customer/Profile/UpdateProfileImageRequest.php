<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /**
             * Profile image file (jpeg, png, webp, max 2MB)
             *
             * @example (binary)
             */
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // 2MB in kilobytes
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => trans('customers.api.profile.image.required'),
            'image.image' => trans('customers.api.profile.image.image'),
            'image.mimes' => trans('customers.api.profile.image.mimes'),
            'image.max' => trans('customers.api.profile.image.max'),
        ];
    }
}
