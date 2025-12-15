<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Rider Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for rider-related settings
    |
    */

    'rider' => [
        /**
         * Trip request timeout in seconds
         * How long a trip request remains valid before expiring
         */
        'trip_request_timeout_seconds' => env('RIDER_TRIP_REQUEST_TIMEOUT_SECONDS', 45),

        /**
         * Rider location update interval in seconds when online (idle)
         * How often online (idle) riders should send their location updates to the server
         */
        'rider_location_update_interval_seconds_online' => env('RIDER_LOCATION_UPDATE_INTERVAL_SECONDS_ONLINE', 45),

        /**
         * Rider location update interval in seconds when busy (on trip)
         * How often busy riders should send their location updates to the server (faster interval)
         */
        'rider_location_update_interval_seconds_busy' => env('RIDER_LOCATION_UPDATE_INTERVAL_SECONDS_BUSY', 15),

        /**
         * Arriving at poll interval in seconds
         * How often to poll the server for "arriving at" time updates
         */
        'arriving_at_poll_interval_seconds' => env('RIDER_ARRIVING_AT_POLL_INTERVAL_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for customer-related settings
    |
    */

    'customer' => [
        /**
         * Arriving at poll interval in seconds
         * How often to poll the server for "arriving at" time updates
         */
        'arriving_at_poll_interval_seconds' => env('CUSTOMER_ARRIVING_AT_POLL_INTERVAL_SECONDS', 60),
    ],

];
