<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Trip Store Request
 *
 * Validates trip booking request data
 */
class TripStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Origin location
            'origin_location_title' => ['required', 'string', 'max:255'],
            'origin_location_sub_title' => ['required', 'string', 'max:255'],
            /**
             * @example 29.353325
             */
            'origin_latitude' => [
                'required',
                'numeric',
                'min:-90',
                'max:90',
            ],
            /**
             * @example 47.98227
             */
            'origin_longitude' => [
                'required',
                'numeric',
                'min:-180',
                'max:180',
            ],

            // Destination location
            'destination_location_title' => ['required', 'string', 'max:255'],
            'destination_location_sub_title' => ['required', 'string', 'max:255'],
            /**
             * @example 29.327636
             */
            'destination_latitude' => [
                'required',
                'numeric',
                'min:-90',
                'max:90',
            ],
            /**
             * @example 47.982201
             */
            'destination_longitude' => [
                'required',
                'numeric',
                'min:-180',
                'max:180',
            ],

            // Ride preferences
            'trip_type_id' => ['required', 'integer', Rule::enum(TripTypeEnum::class)],
            'vehicle_type_id' => ['required', 'integer', Rule::enum(TripVehicleTypeEnum::class)],
            'accessibility_requirements' => ['nullable', 'array'],
            'accessibility_requirements.*' => ['integer', Rule::enum(AccessibilityRequirementsEnum::class)],
            'passenger_count' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            // Origin location
            'origin_location_title.required' => trans('validations.trips.origin_location_title.required'),
            'origin_location_title.string' => trans('validations.trips.origin_location_title.string'),
            'origin_location_title.max' => trans('validations.trips.origin_location_title.max'),
            'origin_location_sub_title.required' => trans('validations.trips.origin_location_sub_title.required'),
            'origin_location_sub_title.string' => trans('validations.trips.origin_location_sub_title.string'),
            'origin_location_sub_title.max' => trans('validations.trips.origin_location_sub_title.max'),
            'origin_latitude.required' => trans('validations.trips.origin_latitude.required'),
            'origin_latitude.numeric' => trans('validations.trips.origin_latitude.numeric'),
            'origin_latitude.min' => trans('validations.trips.origin_latitude.min'),
            'origin_latitude.max' => trans('validations.trips.origin_latitude.max'),
            'origin_longitude.required' => trans('validations.trips.origin_longitude.required'),
            'origin_longitude.numeric' => trans('validations.trips.origin_longitude.numeric'),
            'origin_longitude.min' => trans('validations.trips.origin_longitude.min'),
            'origin_longitude.max' => trans('validations.trips.origin_longitude.max'),

            // Destination location
            'destination_location_title.required' => trans('validations.trips.destination_location_title.required'),
            'destination_location_title.string' => trans('validations.trips.destination_location_title.string'),
            'destination_location_title.max' => trans('validations.trips.destination_location_title.max'),
            'destination_location_sub_title.required' => trans('validations.trips.destination_location_sub_title.required'),
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

            // Ride preferences
            'trip_type_id.required' => trans('validations.trips.trip_type_id.required'),
            'trip_type_id.integer' => trans('validations.trips.trip_type_id.integer'),
            'trip_type_id.enum' => trans('validations.trips.trip_type_id.enum'),
            'vehicle_type_id.required' => trans('validations.trips.vehicle_type_id.required'),
            'vehicle_type_id.integer' => trans('validations.trips.vehicle_type_id.integer'),
            'vehicle_type_id.enum' => trans('validations.trips.vehicle_type_id.enum'),
            'accessibility_requirements.array' => trans('validations.trips.accessibility_requirements.array'),
            'accessibility_requirements.*.integer' => trans('validations.trips.accessibility_requirements.*.integer'),
            'accessibility_requirements.*.enum' => trans('validations.trips.accessibility_requirements.*.enum'),
            'passenger_count.required' => trans('validations.trips.passenger_count.required'),
            'passenger_count.integer' => trans('validations.trips.passenger_count.integer'),
            'passenger_count.min' => trans('validations.trips.passenger_count.min'),
            'passenger_count.max' => trans('validations.trips.passenger_count.max'),
        ];
    }
}
