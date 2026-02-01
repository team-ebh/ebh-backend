<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Models\Trip;
use Illuminate\Validation\Rule;

/**
 * Confirm Trip Request
 *
 * Validates trip confirmation request data including payment method and ride type.
 *
 * For RIDE_NOW trips: ride_type_id is required, destination fields required for ROUND_TRIP/ROUND_TRIP_WAIT.
 * For SCHEDULED trips: ride_type_id is optional (defaults to ONE_WAY), destination fields not needed.
 */
class ConfirmTripRequest extends ChangeRideTypeRequest
{
    /**
     * Prepare the data for validation.
     * Sets default ride_type_id for scheduled trips.
     */
    protected function prepareForValidation(): void
    {
        /** @var Trip $trip */
        $trip = $this->route('trip');

        // For scheduled trips, default ride_type_id to ONE_WAY if not provided
        if ($trip->isScheduledTripType() && ! $this->has('ride_type_id')) {
            $this->merge([
                'ride_type_id' => RideTypeEnum::ONE_WAY->value,
            ]);
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules['payment_method'] = [
            'required',
            'integer',
            Rule::enum(PaymentMethodEnum::class),
            function (string $attribute, mixed $value, \Closure $fail): void {
                $rideTypeId = (int) $this->input('ride_type_id');
                $paymentMethod = (int) $value;

                // Check if ride type is round trip (ROUND_TRIP or ROUND_TRIP_WAIT)
                $isRoundTrip = $rideTypeId === RideTypeEnum::ROUND_TRIP->value;

                // For round trips, only cash payment is allowed
                if ($isRoundTrip && $paymentMethod !== PaymentMethodEnum::CASH->value) {
                    $fail(trans('validations.trips.payment_method.only_cash_allowed_for_round_trip'));
                }
            },
        ];

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
