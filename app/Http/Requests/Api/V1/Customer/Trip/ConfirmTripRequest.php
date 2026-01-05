<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Validation\Rule;

/**
 * Confirm Trip Request
 *
 * Validates trip confirmation request data including payment method and optional ride type changes
 */
class ConfirmTripRequest extends ChangeRideTypeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['payment_method'] = ['required', 'integer', Rule::enum(PaymentMethodEnum::class)];

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'payment_method.required' => trans('validations.trips.payment_method.required'),
            'payment_method.integer' => trans('validations.trips.payment_method.integer'),
            'payment_method.enum' => trans('validations.trips.payment_method.enum'),
        ]);
    }
}
