<?php

declare(strict_types=1);

return [
    'admin' => [
        'arabic_field' => 'The field must contain only Arabic letters and digits.',
    ],
    'api' => [

    ],
    'trips' => [
        'origin_latitude' => [
            'required' => 'Origin latitude is required.',
            'numeric' => 'Origin latitude must be a number.',
            'min' => 'Origin latitude must be at least :min.',
            'max' => 'Origin latitude must not exceed :max.',
        ],
        'origin_longitude' => [
            'required' => 'Origin longitude is required.',
            'numeric' => 'Origin longitude must be a number.',
            'min' => 'Origin longitude must be at least :min.',
            'max' => 'Origin longitude must not exceed :max.',
        ],
        'destination_latitude' => [
            'required' => 'Destination latitude is required.',
            'numeric' => 'Destination latitude must be a number.',
            'min' => 'Destination latitude must be at least :min.',
            'max' => 'Destination latitude must not exceed :max.',
        ],
        'destination_longitude' => [
            'required' => 'Destination longitude is required.',
            'numeric' => 'Destination longitude must be a number.',
            'min' => 'Destination longitude must be at least :min.',
            'max' => 'Destination longitude must not exceed :max.',
        ],
        'trip_type_id' => [
            'required' => 'Trip type is required.',
            'integer' => 'Trip type must be an integer.',
            'enum' => 'The selected trip type is invalid.',
        ],
        'vehicle_type_id' => [
            'required' => 'Vehicle type is required.',
            'integer' => 'Vehicle type must be an integer.',
            'enum' => 'The selected vehicle type is invalid.',
        ],
        'accessibility_requirements' => [
            'array' => 'Accessibility requirements must be an array.',
        ],
        'accessibility_requirements.*' => [
            'integer' => 'Each accessibility requirement must be an integer.',
            'enum' => 'One or more accessibility requirements are invalid.',
        ],
        'passenger_count' => [
            'required' => 'Passenger count is required.',
            'integer' => 'Passenger count must be an integer.',
            'min' => 'At least :min passenger is required.',
            'max' => 'Maximum :max passengers allowed.',
        ],
        'ride_type_id' => [
            'required' => 'Ride type is required.',
            'integer' => 'Ride type must be an integer.',
            'enum' => 'The selected ride type is invalid.',
        ],
        'waiting_time_minutes' => [
            'integer' => 'Waiting time must be an integer.',
            'min' => 'Waiting time must be at least :min minute.',
            'max' => 'Waiting time must not exceed :max minutes.',
        ],
    ],
];
