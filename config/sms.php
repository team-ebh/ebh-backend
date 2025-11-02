<?php

declare(strict_types=1);

// use App\Services\SMS\Enums\SmsProvidersEnum;

return [
    'otp_timeout' => 120,
    'otp_length' => 5,
    'test_mode' => [
        'phone_number' => 44556633,
        'otp_code' => '0421',
    ],
    //    'active_provider' => env('SMS_PROVIDER', SmsProvidersEnum::KWT_SMS->value),
    //
    //    SmsProvidersEnum::ROUTE_MOBILE->value => [
    //        'username' => env('SMS_USERNAME'),
    //        'password' => env('SMS_PASSWORD'),
    //        'source' => env('SMS_SOURCE', 'SEDALIA'),
    //        'url' => env('SMS_URL', 'https://api.rmlconnect.net:8443/OtpApi/otpgenerate'),
    //        'verify_otp_url' => env('SMS_URL_VERIFY_OTP_URL', 'https://api.rmlconnect.net:8443/OtpApi/checkotp'),
    //    ],
    //
    //    SmsProvidersEnum::KWT_SMS->value => [
    //        'username' => env('SMS_USERNAME'),
    //        'password' => env('SMS_PASSWORD'),
    //        'url' => env('SMS_URL', 'https://www.kwtsms.com/API/send/'),
    //    ],
];
