<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Profile;

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
            'image.required' => trans('riders.api.validation.profile.image.required'),
            'image.image' => trans('riders.api.validation.profile.image.image'),
            'image.mimes' => trans('riders.api.validation.profile.image.mimes'),
            'image.max' => trans('riders.api.validation.profile.image.max'),
        ];
    }
}
