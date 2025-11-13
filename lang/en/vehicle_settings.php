<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'Vehicle Settings',
        'page_title' => 'Vehicle Settings',
        'page_heading' => 'Vehicle Settings',

        'sections' => [
            'car_types' => [
                'title' => 'Car Types',
                'description' => 'Manage available car types (e.g., Sedan, SUV, Hatchback)',
            ],
            'car_colors' => [
                'title' => 'Car Colors',
                'description' => 'Manage available car colors (e.g., White, Black, Silver)',
            ],
            'passenger_capacity' => [
                'title' => 'Passenger Capacity',
                'description' => 'Define passenger capacity options (1 to 6 passengers)',
            ],
            'accessibility_features' => [
                'title' => 'Accessibility Features',
                'description' => 'Manage accessibility features (e.g., Wheelchair, Oxygen Support)',
            ],
            'car_makes' => [
                'title' => 'Car Makes',
                'description' => 'Manage car manufacturers (e.g., Toyota, Honda, Ford)',
            ],
            'car_models' => [
                'title' => 'Car Models',
                'description' => 'Manage car models (e.g., Camry, Accord, Mustang)',
            ],
            'vehicle_types' => [
                'title' => 'Vehicle Types',
                'description' => 'Manage vehicle types (e.g., Bed/Stretcher, Oxygen Equipment, Mobility Aid)',
            ],
        ],

        'fields' => [
            'name' => 'Name (English)',
            'name_ar' => 'Name (Arabic)',
            'capacity' => 'Capacity',
        ],

        'labels' => [
            'passengers' => 'passengers',
            'this_item' => 'this item',
        ],

        'actions' => [
            'add_car_type' => 'Add Car Type',
            'add_car_color' => 'Add Car Color',
            'add_capacity' => 'Add Capacity',
            'add_accessibility_feature' => 'Add Accessibility Feature',
            'add_car_make' => 'Add Car Make',
            'add_car_model' => 'Add Car Model',
            'add_vehicle_type' => 'Add Vehicle Type',
            'save_all_changes' => 'Save All Changes',
        ],

        'notifications' => [
            'saved' => 'Vehicle settings have been saved successfully.',
            'error' => 'Error saving vehicle settings',
        ],

        'errors' => [
            'item_in_use' => 'Cannot delete ":item" because it is currently being used by :count rider vehicle(s). Please remove the assignment from the rider(s) first.',
        ],
    ],
];
