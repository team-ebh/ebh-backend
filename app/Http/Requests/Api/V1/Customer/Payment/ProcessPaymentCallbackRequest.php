<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentCallbackRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'track_id' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'track_id.required' => trans('validation.required', ['attribute' => 'track ID']),
            'track_id.string' => trans('validation.string', ['attribute' => 'track ID']),
        ];
    }
}
