<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Location;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => trans('validation.required', ['attribute' => 'latitude']),
            'latitude.numeric' => trans('validation.numeric', ['attribute' => 'latitude']),
            'latitude.between' => trans('validation.between.numeric', ['attribute' => 'latitude', 'min' => -90, 'max' => 90]),
            'longitude.required' => trans('validation.required', ['attribute' => 'longitude']),
            'longitude.numeric' => trans('validation.numeric', ['attribute' => 'longitude']),
            'longitude.between' => trans('validation.between.numeric', ['attribute' => 'longitude', 'min' => -180, 'max' => 180]),
        ];
    }
}
