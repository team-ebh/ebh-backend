<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'App Settings',
        'page_title' => 'App Settings',
        'page_heading' => 'App Settings',

        'tabs' => [
            'pricing' => 'Pricing Settings',
            'scheduling' => 'Scheduling Settings',
        ],

        'sections' => [
            'pricing' => [
                'title' => 'Waiting Time Pricing',
                'description' => 'Configure waiting time pricing for ROUND_TRIP_WAIT rides',
            ],
            'scheduling' => [
                'title' => 'Trip Scheduling',
                'description' => 'Configure settings for scheduled trips',
            ],
        ],

        'fields' => [
            'waiting_time_rate' => 'Waiting Time Rate',
            'waiting_time_interval_minutes' => 'Waiting Time Interval',
            'scheduled_trip_search_start_minutes' => 'Search Start Time',
            'customer_min_return_time_minutes' => 'Minimum Return Time',
        ],

        'helpers' => [
            'waiting_time_rate' => 'Price per waiting time interval (0.100-50.000 KWD)',
            'waiting_time_interval_minutes' => 'Duration of each waiting time interval (5-120 minutes)',
            'scheduled_trip_search_start_minutes' => 'How many minutes before scheduled time to start searching for a rider (1-60 minutes)',
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
