<?php

declare(strict_types=1);

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;

return [
    'statuses' => [
        PaymentStatusEnum::PENDING->name => 'Pending',
        PaymentStatusEnum::PAID->name => 'Paid',
        PaymentStatusEnum::FAILED->name => 'Failed',
        PaymentStatusEnum::EXPIRED->name => 'Expired',
        PaymentStatusEnum::LOCKED->name => 'Locked',
        PaymentStatusEnum::LOCKED_PAID->name => 'Locked (Paid)',
    ],
    'frontend_statuses' => [
        PaymentStatusEnum::PENDING->name => 'Pending',
        PaymentStatusEnum::PAID->name => 'Success',
        PaymentStatusEnum::FAILED->name => 'Failure',
        PaymentStatusEnum::EXPIRED->name => 'Expired',
        PaymentStatusEnum::LOCKED->name => 'Locked',
        PaymentStatusEnum::LOCKED_PAID->name => 'Locked (Paid)',
    ],
    'api' => [
        'payment_methods' => [
            PaymentMethodEnum::KNET->name => 'KNET',
            PaymentMethodEnum::CASH->name => 'Cash',
        ],
        'payment_methods_description' => [
            PaymentMethodEnum::KNET->name => 'Online payment',
            PaymentMethodEnum::CASH->name => 'Pay with cash to driver',
        ],
    ],
    'errors' => [
        'payment_link_generation_failed' => 'Failed to generate payment link',
        'payment_gateway_request_failed' => 'Payment gateway request failed',
        'payment_gateway_failure_status' => 'Payment gateway returned failure status',
        'payment_gateway_missing_link' => 'Payment gateway returned success but missing payment link',
        'payment_not_found' => 'Payment not found',
        'payment_not_paid' => 'Payment has not been paid yet',
        'payment_already_processed' => 'Payment has already been processed',
        'payment_verification_failed' => 'Payment verification failed',
    ],
];
