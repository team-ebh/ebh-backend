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
            'account_disabled' => 'Your account is suspended or inactive. Please contact support.',
            'invalid_otp' => 'Invalid or expired OTP.',
            'before_registered' => 'Customer is already registered.',
            'must_be_registered' => 'Customer must be registered first.',
        ],
    ],
    'admin' => [
        'model_label' => 'Customer',
        'plural_model_label' => 'Customers',
        'navigation_label' => 'Customers',
        'form' => [
            'personal_information' => 'Personal Information',
            'personal_information_description' => 'Customer\'s basic details and contact information',
        ],
        'fields' => [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'status' => 'Status',
            'trip_id' => 'Trip ID',
            'trip_status' => 'Status',
            'trip_type' => 'Type',
            'vehicle_type' => 'Vehicle Type',
            'total_price' => 'Total Price',
        ],
        'relation_managers' => [
            'trips' => 'Trips',
        ],
        'infolist' => [
            'personal_information' => 'Personal Information',
            'personal_information_description' => 'Customer\'s basic details and contact information',
            'status_information' => 'Account Status',
            'status_information_description' => 'Current status and available actions',
            'trips' => 'Trips History',
            'trips_description' => 'All trips made by this customer',
        ],
        'status' => [
            CustomerStatusEnum::PENDING_VERIFICATION->name => 'Pending Verification',
            CustomerStatusEnum::ACTIVE->name => 'Active',
            CustomerStatusEnum::INACTIVE->name => 'Inactive',
            CustomerStatusEnum::SUSPENDED->name => 'Suspended',
        ],
        'actions' => [
            'activate' => 'Activate',
            'deactivate' => 'Deactivate',
            'suspend' => 'Suspend',
        ],
        'notifications' => [
            'activated' => 'Customer has been activated successfully.',
            'deactivated' => 'Customer has been deactivated successfully.',
            'suspended' => 'Customer has been suspended successfully.',
        ],
        'exceptions' => [
            'has_active_trip' => 'Cannot disable customer. The customer has an active trip in progress.',
            'has_pending_payment' => 'Cannot disable customer. The customer has a pending payment that must be completed first.',
        ],
        'stats' => [
            'total_customers' => 'Total Customers',
            'total_customers_description' => 'All registered customers',
            'active_customers' => 'Active Customers',
            'active_customers_description' => 'Currently active accounts',
            'new_this_month' => 'New This Month',
            'new_this_month_description' => 'Registered this month',
            'suspended_users' => 'Suspended Users',
            'suspended_users_description' => 'Suspended accounts',
        ],
    ],
];
