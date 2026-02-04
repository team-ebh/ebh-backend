<?php

declare(strict_types=1);

return [
    // SMS Message Templates
    'signup' => 'Your verification code is: :otp',
    'signin' => 'Your verification code is: :otp',
    'delete_account' => 'Your account deletion verification code is: :otp',

    // Admin Panel - SMS Logs Resource
    'resource' => [
        'label' => 'SMS Log',
        'plural_label' => 'SMS Logs',
        'navigation_label' => 'SMS Logs',
    ],

    // Table Columns
    'table' => [
        'receiver_type' => 'Receiver Type',
        'phone_number' => 'Phone Number',
        'sms_type' => 'SMS Type',
        'provider' => 'Provider',
        'status' => 'Status',
        'http_status' => 'HTTP Status',
        'message' => 'Message',
        'sent_at' => 'Sent At',
        'created_at' => 'Created At',
    ],

    // Infolist Labels
    'infolist' => [
        'phone_number' => 'Phone Number',
        'delivery_status' => 'Delivery Status',
        'type' => 'Type',
        'receiver' => 'Receiver',
        'provider' => 'Provider',
        'sent_at' => 'Sent At',
        'http_status' => 'HTTP Status',
        'sms_message' => 'SMS Message',
        'request_payload' => 'Request Payload',
        'provider_response' => 'Provider Response',
        'technical_details' => 'Technical Details',
        'no_message' => 'No message content available',
        'no_request_data' => 'No request data',
        'no_response_data' => 'No response data',
        'phone_copied' => 'Phone number copied!',
        'request_copied' => 'Request data copied!',
        'response_copied' => 'Response data copied!',
    ],

    // Filters
    'filters' => [
        'status' => 'Status',
        'all' => 'All',
        'success' => 'Success',
        'failed' => 'Failed',
        'sms_type' => 'SMS Type',
        'provider' => 'Provider',
        'receiver_type' => 'Receiver Type',
    ],

    // SMS Types
    'types' => [
        'signup' => 'Sign Up',
        'signin' => 'Sign In',
        'delete_account' => 'Delete Account',
    ],

    // Providers
    'providers' => [
        'kwt_sms' => 'KWT SMS',
        'route_mobile' => 'Route Mobile',
    ],

    // Receiver Types
    'receiver_types' => [
        'customer' => 'Customer',
        'rider' => 'Rider',
    ],
];
