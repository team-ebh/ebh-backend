<?php

declare(strict_types=1);

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;

return [
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
            TripStatusEnum::PENDING->name => 'Pending',
            TripStatusEnum::CONFIRMED->name => 'Confirmed',
            TripStatusEnum::DRIVER_ASSIGNED->name => 'Driver Assigned',
            TripStatusEnum::IN_PROGRESS->name => 'In Progress',
            TripStatusEnum::ARRIVED->name => 'Arrived',
            TripStatusEnum::COMPLETED->name => 'Completed',
            TripStatusEnum::CANCELLED->name => 'Cancelled',
            TripStatusEnum::CANCELLED_BY_DRIVER->name => 'Cancelled by Driver',
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
    ],
];
