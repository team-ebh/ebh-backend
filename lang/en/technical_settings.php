<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'Technical Settings',
        'page_title' => 'Technical Settings',
        'page_heading' => 'Technical Settings',

        'tabs' => [
            'rider' => 'Rider Settings',
            'customer' => 'Customer Settings',
        ],

        'sections' => [
            'rider' => [
                'title' => 'Rider App Configuration',
                'description' => 'Configure settings for the rider mobile application',
            ],
            'customer' => [
                'title' => 'Customer App Configuration',
                'description' => 'Configure settings for the customer mobile application',
            ],
        ],

        'fields' => [
            'rider_trip_request_timeout_seconds' => 'Trip Request Timeout',
            'rider_location_update_interval_online' => 'Location Update Interval (Online)',
            'rider_location_update_interval_busy' => 'Location Update Interval (Busy)',
            'rider_arriving_at_poll_interval' => 'Arriving At Poll Interval',
            'customer_arriving_at_poll_interval' => 'Arriving At Poll Interval',
            'customer_min_return_time_minutes' => 'Minimum Return Time',
        ],

        'helpers' => [
            'rider_trip_request_timeout_seconds' => 'How long a trip request remains valid before expiring (10-300 seconds)',
            'rider_location_update_interval_online' => 'How often online (idle) riders should send location updates (10-300 seconds)',
            'rider_location_update_interval_busy' => 'How often busy riders should send location updates (5-120 seconds)',
            'rider_arriving_at_poll_interval' => 'How often to poll for "arriving at" time updates (10-300 seconds)',
            'customer_arriving_at_poll_interval' => 'How often to poll for "arriving at" time updates (10-300 seconds)',
            'customer_min_return_time_minutes' => 'Minimum time before customer can schedule return pickup (15-480 minutes)',
        ],

        'units' => [
            'seconds' => 'seconds',
            'minutes' => 'minutes',
        ],

        'actions' => [
            'save' => 'Save Settings',
        ],

        'notifications' => [
            'saved' => 'Settings saved successfully',
            'error' => 'Error saving settings',
        ],
    ],
];
