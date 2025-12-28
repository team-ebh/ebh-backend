<?php

declare(strict_types=1);

namespace App\Enums\Customer;

/**
 * Customer App State Enum
 *
 * Represents the current state of the customer app
 */
enum CustomerAppStateEnum: string
{
    /**
     * Customer has no active trip
     */
    case NO_TRIP = 'NO_TRIP';

    /**
     * Customer has an active trip
     */
    case HAS_ACTIVE_TRIP = 'HAS_ACTIVE_TRIP';

    /**
     * Customer has an active trip
     */
    case HAS_PENDING_PAYMENT = 'HAS_PENDING_PAYMENT';
}
