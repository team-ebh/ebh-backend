<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use App\Enums\Trip\RideTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Change Ride Type Request
 *
 * Validates ride type change and pricing calculation request data
 */
class ChangeRideTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $rideTypeId = $this->post('ride_type_id');
        $isRoundTrip = $rideTypeId === RideTypeEnum::ROUND_TRIP->value;
        $isRoundTripWait = $rideTypeId === RideTypeEnum::ROUND_TRIP_WAIT->value;

        return [
            /**
             * Ride type ID
             *
             * @example 2
             */
            'ride_type_id' => ['required', 'integer', Rule::enum(RideTypeEnum::class)],

            /**
             * Destination location title
             *
             * @example Kuwait City
             */
            'destination_location_title' => [
                $isRoundTrip || $isRoundTripWait ? 'required' : 'nullable',
                'string',
                'max:255',
            ],

            /**
             * Destination location subtitle
             *
             * @example Near the main square
             */
            'destination_location_sub_title' => [
                $isRoundTrip || $isRoundTripWait ? 'required' : 'nullable',
                'string',
                'max:255',
            ],

            /**
             * Destination latitude
             *
             * @example 29.353325
             */
            'destination_latitude' => [
                $isRoundTrip || $isRoundTripWait ? 'required' : 'nullable',
                'numeric',
                'min:-90',
                'max:90',
            ],

            /**
             * Destination longitude
             *
             * @example 47.98227
             */
            'destination_longitude' => [
                $isRoundTrip || $isRoundTripWait ? 'required' : 'nullable',
                'numeric',
                'min:-180',
                'max:180',
            ],

            /**
             * Return time (Unix timestamp, required for ROUND_TRIP)
             *
             * @example 1766759492
             */
            'return_time' => [
                $isRoundTrip ? 'required' : 'nullable',
                'integer',
                'min:' . now()->timestamp, // Must be in the future
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ride_type_id.required' => trans('validations.trips.ride_type_id.required'),
            'ride_type_id.integer' => trans('validations.trips.ride_type_id.integer'),
            'ride_type_id.enum' => trans('validations.trips.ride_type_id.enum'),
            'return_time.required' => trans('validations.trips.return_time.required'),
            'return_time.integer' => trans('validations.trips.return_time.integer'),
            'return_time.min' => trans('validations.trips.return_time.min'),
            'destination_location_title.required' => trans('validations.trips.destination_location_title.required'),
            'destination_location_title.string' => trans('validations.trips.destination_location_title.string'),
            'destination_location_title.max' => trans('validations.trips.destination_location_title.max'),
            'destination_location_sub_title.string' => trans('validations.trips.destination_location_sub_title.string'),
            'destination_location_sub_title.max' => trans('validations.trips.destination_location_sub_title.max'),
            'destination_latitude.required' => trans('validations.trips.destination_latitude.required'),
            'destination_latitude.numeric' => trans('validations.trips.destination_latitude.numeric'),
            'destination_latitude.min' => trans('validations.trips.destination_latitude.min'),
            'destination_latitude.max' => trans('validations.trips.destination_latitude.max'),
            'destination_longitude.required' => trans('validations.trips.destination_longitude.required'),
            'destination_longitude.numeric' => trans('validations.trips.destination_longitude.numeric'),
            'destination_longitude.min' => trans('validations.trips.destination_longitude.min'),
            'destination_longitude.max' => trans('validations.trips.destination_longitude.max'),
        ];
    }
}
