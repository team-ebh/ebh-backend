<?php

declare(strict_types=1);

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripRequestStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;

return [
    'location_types' => [
        'origin' => 'Origin',
        'destination' => 'Destination',
    ],
    'location_statuses' => [
        'draft' => 'Draft',
        'pending_rider' => 'Pending Rider',
        'accepted_rider' => 'Accepted by Rider',
        'arrived' => 'Arrived',
        'canceled_by_customer' => 'Cancelled by Customer',
        'cancelled_by_rider' => 'Cancelled by Rider',
        'picked_up' => 'Picked Up',
        'completed' => 'Completed',
    ],
    'api' => [
        'trip_types' => [
            TripTypeEnum::RIDE_NOW->name => 'Ride Now',
            TripTypeEnum::SCHEDULED->name => 'Scheduled',
        ],
        'ride_types' => [
            RideTypeEnum::ONE_WAY->name => 'One-way ride',
            RideTypeEnum::ONE_WAY->name . '_description' => 'Single ride without return',
            RideTypeEnum::ROUND_TRIP->name => 'Round trip',
            RideTypeEnum::ROUND_TRIP->name . '_description' => 'Go and come back',
            RideTypeEnum::ROUND_TRIP_WAIT->name => 'Round trip with wait',
            RideTypeEnum::ROUND_TRIP_WAIT->name . '_description' => 'Driver waits until ready',
        ],
        'vehicle_types' => [
            TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->name => 'Wheelchair Accessible',
            TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->name . '_description' => 'Standard Wheelchair transport',
            TripVehicleTypeEnum::BED_TRANSPORT->name => 'Bed Transport',
            TripVehicleTypeEnum::BED_TRANSPORT->name . '_description' => 'For stretcher and bed transport',
        ],
        'accessibility_requirements' => [
            AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->name => 'Wheelchair Accessible',
            AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->name . '_description' => 'Standard Wheelchair transport',
            AccessibilityRequirementsEnum::OXYGEN_SUPPORT->name => 'Oxygen Support',
            AccessibilityRequirementsEnum::OXYGEN_SUPPORT->name . '_description' => 'Portable oxygen support',
            AccessibilityRequirementsEnum::PORTABLE_RAMP->name => 'Portable Ramp',
            AccessibilityRequirementsEnum::PORTABLE_RAMP->name . '_description' => 'Equipped with ramp access',
        ],
        'trip_statuses' => [
            TripStatusEnum::DRAFT->name => 'Draft',
            TripStatusEnum::PENDING_RIDER->name => 'Pending Rider',
            TripStatusEnum::ACCEPTED_RIDER->name => 'Accepted by Rider',
            TripStatusEnum::ON_TRIP->name => 'On Trip',
            TripStatusEnum::COMPLETED->name => 'Completed',
            TripStatusEnum::CANCELED_BY_CUSTOMER->name => 'Cancelled by Customer',
            TripStatusEnum::CANCELLED_BY_RIDER->name => 'Cancelled by Rider',
        ],
        'trip_request_statuses' => [
            TripRequestStatusEnum::PENDING->name => 'Pending',
            TripRequestStatusEnum::ACCEPTED->name => 'Accepted',
            TripRequestStatusEnum::DECLINED->name => 'Declined',
            TripRequestStatusEnum::EXPIRED->name => 'Expired',
            TripRequestStatusEnum::CANCELLED->name => 'Cancelled',
            TripRequestStatusEnum::LOCKED->name => 'Locked',
        ],
        'trip_location_statuses' => [
            'PENDING' => 'Pending',
            'ARRIVED' => 'Arrived',
            'PICKED_UP' => 'Picked Up',
            'DROPPED_OFF' => 'Dropped Off',
            'COMPLETED' => 'Completed',
        ],
        'price_estimation' => 'Price Estimation',
        'waiting_time_rate_description' => ':price per :minutes minutes',
        'time_units' => [
            'minutes' => 'minutes',
            'hours' => 'hours',
        ],
        'breakdown' => [
            'base_fare' => 'Base Fare',
            'one_way' => 'One Way',
            'return_fare' => 'Return Fare',
            'return_trip' => 'Return Trip',
            'round_trip_fee' => 'Round Trip',
            'waiting_time_charge' => 'Waiting Time Charge',
            'accessibility_services' => 'Accessibility Services',
            'to_be_calculated' => 'To be calculated',
            'included' => 'Included',
        ],
        'exceptions' => [
            'trip_not_pending' => 'This trip cannot be confirmed. Only draft trips can be confirmed.',
            'trip_not_draft' => 'Ride type can only be changed for draft trips.',
            'trip_cannot_be_cancelled' => 'This trip cannot be cancelled. Only draft or pending rider trips can be cancelled.',
            'customer_already_has_active_trip' => 'You already have an active trip. Please complete or cancel your current trip before creating a new one.',
            'rider_location_not_available' => 'Rider location is not available. Location tracking is only available when rider is accepted, arrived, or picked up.',
            'trip_status_cannot_be_checked' => 'Trip status cannot be checked. Status checking is not available for draft, cancelled, or completed trips.',
        ],
    ],
    'not_your_trip' => 'You are not authorized to access this trip.',
    'cannot_cancel_trip_status' => 'This trip cannot be cancelled. Only accepted or arrived trips can be cancelled by rider.',
    'trip_cancelled_successfully' => 'Trip cancelled successfully',
    'trip_request_not_belong_to_rider' => 'This trip request is not assigned to you.',
    'trip_request_declined_successfully' => 'Trip request declined successfully.',
    'no_active_trip' => 'You do not have any active trip.',
    'invalid_trip_action' => 'Invalid trip action. Please check the current trip status.',
    'location_not_available' => 'Location is not available for this action.',
    'trip_not_in_progress' => 'Trip is not in progress.',
    'arrived_at_location_successfully' => 'Arrived at location successfully.',
    'passenger_picked_up_successfully' => 'Passenger picked up successfully.',
    'location_completed_successfully' => 'Location completed successfully.',
    'trip_completed_successfully' => 'Trip completed successfully.',
];
