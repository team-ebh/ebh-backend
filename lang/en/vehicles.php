<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'Vehicles',
        'page_title' => 'Vehicles',
        'page_heading' => 'Vehicles',

        'sections' => [
            'basic_info' => [
                'title' => 'Basic Information',
                'description' => 'Essential vehicle and ownership details',
            ],
            'vehicle_details' => [
                'title' => 'Vehicle Details',
                'description' => 'Specifications and characteristics',
            ],
            'accessibility' => [
                'title' => 'Accessibility Features',
                'description' => 'Special equipment and accessibility features',
            ],
            'vehicles' => [
                'title' => 'Vehicles',
                'description' => 'Manage rider vehicles and their specifications',
            ],
            'vehicle_information' => [
                'title' => 'Vehicle Information',
                'description' => 'Vehicle specifications and details for this rider',
            ],
        ],

        'fields' => [
            'rider' => 'Rider',
            'plate_number' => 'Plate Number',
            'year' => 'Year',
            'car_type' => 'Car Type',
            'car_color' => 'Car Color',
            'car_make' => 'Car Make',
            'car_model' => 'Car Model',
            'passenger_capacity' => 'Passenger Capacity',
            'vehicle_type' => 'Vehicle Type',
            'accessibility_features' => 'Accessibility Features',
        ],

        'labels' => [
            'new_vehicle' => 'New Vehicle',
        ],

        'actions' => [
            'add_vehicle' => 'Add Vehicle',
        ],
    ],
];
