<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RealTime Cache Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "redis", "tile38" (future)
    |
    */
    'driver' => env('REALTIME_CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'connection' => env('REALTIME_CACHE_REDIS_CONNECTION', 'default'),
        'prefix' => env('REALTIME_CACHE_PREFIX', 'rtc:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rider Location Settings
    |--------------------------------------------------------------------------
    */
    'rider_location' => [
        // TTL for rider location (seconds) - expires if no update
        'ttl' => (int) env('RTC_RIDER_LOCATION_TTL', 30),

        // Maximum search radius in meters
        'max_search_radius' => (int) env('RTC_MAX_SEARCH_RADIUS', 50000),

        // Default search radius in meters
        'default_search_radius' => (int) env('RTC_DEFAULT_SEARCH_RADIUS', 5000),

        // Maximum number of nearby riders to return
        'max_nearby_results' => (int) env('RTC_MAX_NEARBY_RESULTS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rider Status Settings
    |--------------------------------------------------------------------------
    */
    'rider_status' => [
        // TTL for online status (seconds) - after this, rider is considered offline
        'ttl' => (int) env('RTC_RIDER_STATUS_TTL', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trip Cache Settings
    |--------------------------------------------------------------------------
    */
    'trip' => [
        // TTL for each trip status (seconds)
        'ttl' => [
            'pending' => (int) env('RTC_TRIP_TTL_PENDING', 600),        // 10 min
            'accepted' => (int) env('RTC_TRIP_TTL_ACCEPTED', 1800),     // 30 min
            'arrived' => (int) env('RTC_TRIP_TTL_ARRIVED', 1800),       // 30 min
            'picked_up' => (int) env('RTC_TRIP_TTL_PICKED_UP', 86400),  // 24 hours
            'completed' => (int) env('RTC_TRIP_TTL_COMPLETED', 1800),   // 30 min
            'cancelled' => (int) env('RTC_TRIP_TTL_CANCELLED', 300),    // 5 min
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Settings
    |--------------------------------------------------------------------------
    */
    'lock' => [
        // Default lock TTL in milliseconds
        'ttl' => (int) env('RTC_LOCK_TTL', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    |
    | When Redis is unavailable, the system falls back to database.
    | These settings control the circuit breaker behavior.
    |
    */
    'fallback' => [
        // Enable/disable fallback to database
        'enabled' => env('RTC_FALLBACK_ENABLED', true),

        // Cooldown period (seconds) before retrying Redis after failure
        'cooldown_seconds' => (int) env('RTC_FALLBACK_COOLDOWN', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific features or the entire cache functionality.
    | Useful for debugging or when a feature has issues.
    |
    | When a feature is disabled, it uses Database instead of cache driver.
    |
    */
    'features' => [
        // Master switch: disable to use only Database (no cache at all)
        'enabled' => env('RTC_ENABLED', true),

        // Individual feature switches (only apply when enabled is true)
        // Rider GPS location tracking with GEO queries
        'rider_geolocation' => env('RTC_RIDER_GEOLOCATION', true),

        // Rider online/offline/busy status tracking
        'rider_online_status' => env('RTC_RIDER_ONLINE_STATUS', true),

        // Active trip data caching
        'trip_cache' => env('RTC_TRIP_CACHE', true),

        // Distributed locking for rider assignment (prevents double-assignment)
        'assignment_lock' => env('RTC_ASSIGNMENT_LOCK', true),
    ],
];
