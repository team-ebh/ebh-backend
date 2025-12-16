<?php

declare(strict_types=1);

namespace App\Enums\Payment;

/**
 * Payment Log Type Enum
 *
 * Defines the type of payment gateway operation being logged
 */
enum PaymentLogTypeEnum: string
{
    /**
     * Generate payment link operation
     * Used when creating a new payment link for the customer
     */
    case GENERATE_LINK = 'generate_link';

    /**
     * Check payment status operation
     * Used when verifying payment status with the gateway
     */
    case CHECK_STATUS = 'check_status';

    /**
     * Get the label for display
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::GENERATE_LINK => 'Generate Link',
            self::CHECK_STATUS => 'Check Status',
        };
    }
}
