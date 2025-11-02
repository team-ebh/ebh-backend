<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerStatusEnum;

return [
    'api' => [
        'auth' => [
            'first_name' => [
                'required' => 'First name is required.',
                'string' => 'First name must be a string.',
                'max' => 'First name may not be greater than 30 characters.',
            ],
            'last_name' => [
                'required' => 'Last name is required.',
                'string' => 'Last name must be a string.',
                'max' => 'Last name may not be greater than 30 characters.',
            ],
            'email' => [
                'email' => 'Email must be a valid email address.',
                'max' => 'Email may not be greater than 255 characters.',
            ],
            'phone_number' => [
                'required' => 'Phone number is required.',
                'unique' => 'Phone number is already registered.',
                'regex' => 'Phone number format is invalid.',
                'not_found' => 'Phone number not found or account is not active.',
            ],
            'otp' => [
                'required' => 'OTP is required.',
                'string' => 'OTP must be a string.',
                'size' => 'OTP must be exactly 4 digits.',
            ],
            'otp_sent' => 'OTP has been sent to your phone number.',
            'otp_invalid' => 'Invalid or expired OTP.',
            'customer_not_found' => 'Customer not found.',
            'account_disabled' => 'Your account is disabled.',
        ],
        'exceptions' => [
            'not_found' => 'Customer not found.',
            'account_disabled' => 'Your account is disabled.',
            'invalid_otp' => 'Invalid or expired OTP.',
            'before_registered' => 'Customer is already registered.',
            'must_be_registered' => 'Customer must be registered first.',
        ],
        'status' => [
            CustomerStatusEnum::PENDING_VERIFICATION->name => 'Pending Verification',
            CustomerStatusEnum::ACTIVE->name => 'Active',
        ],
    ],
];
