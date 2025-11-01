<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'company' => 'Company',
            'status' => 'Status',
        ],
        'statuses' => [
            'online' => 'Online',
            'offline' => 'Offline',
            'busy' => 'Busy',
        ],
        'model_label' => 'Rider',
        'plural_model_label' => 'Riders',
        'navigation_label' => 'Riders',
        'stats' => [
            'total_riders' => 'Total Riders',
            'total_riders_description' => 'All registered riders',
            'online_now' => 'Online Now',
            'online_now_description' => 'Currently online riders',
            'accessibility_certified' => 'Accessibility Certified',
            'accessibility_certified_description' => 'Riders with accessibility training',
            'avg_rating' => 'Avg Rating',
            'avg_rating_description' => 'Average rider rating',
        ],
        'infolist' => [
            'personal_information' => 'Personal Information',
            'status_information' => 'Status Information',
            'timestamps' => 'Timestamps',
        ],
    ],
    'api' => [

    ],
];
