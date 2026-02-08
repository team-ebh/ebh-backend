<?php

declare(strict_types=1);

// use App\Enums\SMS\SmsProvidersEnum;

use App\Enums\SMS\SmsProvidersEnum;

return [
    'otp_timeout' => 120,

    /*
    |--------------------------------------------------------------------------
    | SMS Test Mode Configuration
    |--------------------------------------------------------------------------
    |
    | When test mode is enabled, no SMS will be sent to ANY phone number.
    | Test phone numbers will NEVER receive SMS in ANY environment.
    | This prevents accidental SMS charges during development/testing.
    |
    */
    'test_mode' => [
        // Default OTP for testing (used when test mode is enabled)
        'default_otp' => env('SMS_TEST_OTP', '0421'),

        // Phone numbers that will NEVER receive real SMS (in any environment)
        // Add development/testing phone numbers here
        'test_phone_numbers' => [
            '44556633',  // Test number 1
            '65656565',  // Test number 2
        ],
    ],

    'active_provider' => env('SMS_PROVIDER', SmsProvidersEnum::KWT_SMS->value),

    SmsProvidersEnum::ROUTE_MOBILE->value => [
        'username' => env('SMS_USERNAME'),
        'password' => env('SMS_PASSWORD'),
        'source' => env('SMS_SOURCE', 'SEDALIA'),
        'url' => env('SMS_URL', 'https://api.rmlconnect.net:8443/OtpApi/otpgenerate'),
        'verify_otp_url' => env('SMS_URL_VERIFY_OTP_URL', 'https://api.rmlconnect.net:8443/OtpApi/checkotp'),
    ],

    SmsProvidersEnum::KWT_SMS->value => [
        'username' => env('SMS_USERNAME'),
        'password' => env('SMS_PASSWORD'),
        'url' => env('SMS_URL', 'https://www.kwtsms.com/API/send/'),
    ],
];
