<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancel Trip Request
 *
 * Validates trip cancellation request
 */
class CancelTripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.string' => trans('validations.trips.cancellation_reason.string'),
            'cancellation_reason.max' => trans('validations.trips.cancellation_reason.max'),
        ];
    }
}
