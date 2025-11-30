<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Trip Request Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for trip request matching and lifecycle management
    |
    */

    'request' => [
        /*
        |--------------------------------------------------------------------------
        | Expiration Time
        |--------------------------------------------------------------------------
        |
        | How long a trip request remains valid before expiring (in seconds)
        | Set to null for no expiration
        |
        */
        'expiration_seconds' => env('TRIP_REQUEST_EXPIRATION_SECONDS', 180),

        /*
        |--------------------------------------------------------------------------
        | Progressive Search Radiuses
        |--------------------------------------------------------------------------
        |
        | Search radiuses in meters for finding eligible riders
        | The system will progressively expand the search radius if no riders accept
        |
        | Example: [200, 400, 800] means:
        | - First attempt: search within 200 meters
        | - Second attempt: search within 400 meters
        | - Third attempt: search within 800 meters
        |
        */
        'search_radiuses' => [
            200,  // First attempt: 200 meters
            400,  // Second attempt: 400 meters
            800,  // Third attempt: 800 meters
            2000, // Fourth attempt: 2 km (fallback)
        ],

        /*
        |--------------------------------------------------------------------------
        | Maximum Search Attempts
        |--------------------------------------------------------------------------
        |
        | Maximum number of search attempts before giving up
        |
        */
        'max_search_attempts' => 4,

        /*
        |--------------------------------------------------------------------------
        | Retry After All Declined
        |--------------------------------------------------------------------------
        |
        | Should the system automatically retry with a larger radius if all riders
        | in the current radius decline?
        |
        */
        'auto_retry_on_all_declined' => env('TRIP_REQUEST_AUTO_RETRY', true),

        /*
        |--------------------------------------------------------------------------
        | Auto Expire Check Interval
        |--------------------------------------------------------------------------
        |
        | How often to check for expired trip requests (in minutes)
        | This is used by the scheduled job
        |
        */
        'expire_check_interval_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rider Matching Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for finding eligible riders
    |
    */

    'matching' => [
        /*
        |--------------------------------------------------------------------------
        | Match by Proximity
        |--------------------------------------------------------------------------
        |
        | Should proximity (distance) be considered when matching riders?
        |
        */
        'use_proximity' => env('TRIP_MATCHING_USE_PROXIMITY', false),

        /*
        |--------------------------------------------------------------------------
        | Match by Vehicle Type
        |--------------------------------------------------------------------------
        |
        | Riders must have a vehicle that matches the trip's vehicle type requirement
        |
        */
        'require_vehicle_type_match' => false,

        /*
        |--------------------------------------------------------------------------
        | Match by Accessibility Requirements
        |--------------------------------------------------------------------------
        |
        | Riders must have the required accessibility certifications
        |
        */
        'require_accessibility_match' => false,

        /*
        |--------------------------------------------------------------------------
        | Exclude Busy Riders
        |--------------------------------------------------------------------------
        |
        | Should busy riders be excluded from search results?
        |
        */
        'exclude_busy_riders' => true,

        /*
        |--------------------------------------------------------------------------
        | Only Online Riders
        |--------------------------------------------------------------------------
        |
        | Only search for riders who are currently online
        |
        */
        'only_online_riders' => false,
    ],
];
