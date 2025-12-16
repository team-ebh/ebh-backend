<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Confirm Trip Request
 *
 * Validates trip confirmation request data including payment method
 */
class ConfirmTripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /**
             * Payment method ID
             *
             * @example 1
             */
            'payment_method' => ['nullable', 'integer', Rule::enum(PaymentMethodEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => trans('validations.trips.payment_method.required'),
            'payment_method.integer' => trans('validations.trips.payment_method.integer'),
            'payment_method.enum' => trans('validations.trips.payment_method.enum'),
        ];
    }
}
