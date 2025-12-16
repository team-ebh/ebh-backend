<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This value determines which payment gateway will be used by default.
    | Supported: "upayments", "myfatoorah"
    |
    */
    'default_gateway' => env('PAYMENT_GATEWAY', 'upayments'),

    /*
    |--------------------------------------------------------------------------
    | Enable Payment Link Expiration
    |--------------------------------------------------------------------------
    |
    | Enable or disable payment link expiration.
    | When false, payment links will never expire and will be reused indefinitely.
    | When true, payment links will expire based on link_expiration_minutes config.
    | Default: false
    |
    */
    'enable_expiration' => env('PAYMENT_ENABLE_EXPIRATION', false),

    /*
    |--------------------------------------------------------------------------
    | Payment Link Expiration Time
    |--------------------------------------------------------------------------
    |
    | This value determines how long a payment link is valid (in minutes).
    | Only used when enable_expiration is true.
    | Default: 15 minutes
    |
    */
    'link_expiration_minutes' => env('PAYMENT_LINK_EXPIRATION_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Payment Link Reuse Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum remaining time (in minutes) to reuse an existing pending payment link.
    | If a pending payment has more than this time remaining, the existing link will be reused.
    | Only used when enable_expiration is true.
    | Default: 1 minute
    |
    */
    'link_reuse_threshold_minutes' => env('PAYMENT_LINK_REUSE_THRESHOLD_MINUTES', 1),

    /*
    |--------------------------------------------------------------------------
    | Deeplink Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for deep linking to mobile app after payment callback
    | Used to redirect users back to the app with payment result
    |
    */
    'deeplink_base_url' => env('PAYMENT_DEEPLINK_BASE_URL', 'ebhapp://payment'),

    /*
    |--------------------------------------------------------------------------
    | UPayments Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for UPayments gateway (Kuwait)
    | Documentation: https://developers.upayments.com
    |
    */
    'upayments' => [
        'test_mode' => env('UPAYMENTS_TEST_MODE', true),
        'api_key' => env('UPAYMENTS_API_KEY'),
        'test_url' => env('UPAYMENTS_TEST_URL', 'https://sandboxapi.upayments.com'),
        'live_url' => env('UPAYMENTS_LIVE_URL', 'https://api.upayments.com'),
        'callback_url' => env('UPAYMENTS_CALLBACK_URL', getApiUrl() . '/v1/customers/payment/callback'),
        'webhook_url' => env('UPAYMENTS_WEBHOOK_URL', getApiUrl() . '/v1/customers/payment/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MyFatoorah Configuration (Future)
    |--------------------------------------------------------------------------
    |
    | Configuration for MyFatoorah gateway
    | Will be implemented when needed
    |
    */
    'myfatoorah' => [
        'test_mode' => env('MYFATOORAH_TEST_MODE', true),
        'api_key' => env('MYFATOORAH_API_KEY'),
        'test_url' => env('MYFATOORAH_TEST_URL', 'https://apitest.myfatoorah.com'),
        'live_url' => env('MYFATOORAH_LIVE_URL', 'https://api.myfatoorah.com'),
    ],
];
