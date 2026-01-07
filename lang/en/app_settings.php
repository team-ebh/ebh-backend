<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'App Settings',
        'page_title' => 'App Settings',
        'page_heading' => 'App Settings',

        'tabs' => [
            'rider' => 'Rider Settings',
            'customer' => 'Customer Settings',
            'pricing' => 'Pricing Settings',
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
            'pricing' => [
                'title' => 'Waiting Time Pricing',
                'description' => 'Configure waiting time pricing for ROUND_TRIP_WAIT rides',
            ],
        ],

        'fields' => [
            'rider_trip_request_timeout_seconds' => 'Trip Request Timeout',
            'rider_location_update_interval_online' => 'Location Update Interval (Online)',
            'rider_location_update_interval_busy' => 'Location Update Interval (Busy)',
            'rider_arriving_at_poll_interval' => 'Arriving At Poll Interval',
            'customer_arriving_at_poll_interval' => 'Arriving At Poll Interval',
            'customer_min_return_time_minutes' => 'Minimum Return Time',
            'waiting_time_rate' => 'Waiting Time Rate',
            'waiting_time_interval_minutes' => 'Waiting Time Interval',
        ],

        'helpers' => [
            'rider_trip_request_timeout_seconds' => 'How long a trip request remains valid before expiring (10-300 seconds)',
            'rider_location_update_interval_online' => 'How often online (idle) riders should send location updates (10-300 seconds)',
            'rider_location_update_interval_busy' => 'How often busy riders should send location updates (5-120 seconds)',
            'rider_arriving_at_poll_interval' => 'How often to poll for "arriving at" time updates (10-300 seconds)',
            'customer_arriving_at_poll_interval' => 'How often to poll for "arriving at" time updates (10-300 seconds)',
            'customer_min_return_time_minutes' => 'Minimum time before customer can schedule return pickup (15-480 minutes)',
            'waiting_time_rate' => 'Price per waiting time interval (0.100-50.000 KWD)',
            'waiting_time_interval_minutes' => 'Duration of each waiting time interval (5-120 minutes)',
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
