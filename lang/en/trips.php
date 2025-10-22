<?php

declare(strict_types=1);

return [
    'api' => [
        'trip_types' => [
            'RIDE_NOW' => 'Ride Now',
            'SCHEDULED' => 'Scheduled',
        ],
        'vehicle_types' => [
            'WHEELCHAIR_ACCESSIBLE' => 'Wheelchair Accessible',
            'WHEELCHAIR_ACCESSIBLE_description' => 'Standard Wheelchair transport',
            'BED_TRANSPORT' => 'Bed Transport',
            'BED_TRANSPORT_description' => 'For stretcher and bed transport',
        ],
        'accessibility_requirements' => [
            'WHEELCHAIR_ACCESSIBLE' => 'Wheelchair Accessible',
            'WHEELCHAIR_ACCESSIBLE_description' => 'Standard Wheelchair transport',
            'OXYGEN_SUPPORT' => 'Oxygen Support',
            'OXYGEN_SUPPORT_description' => 'Portable oxygen support',
            'PORTABLE_RAMP' => 'Portable Ramp',
            'PORTABLE_RAMP_description' => 'Equipped with ramp access',
        ],
    ],
];
