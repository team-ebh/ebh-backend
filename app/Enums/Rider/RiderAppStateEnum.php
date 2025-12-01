<?php

declare(strict_types=1);

namespace App\Enums\Rider;

/**
 * Rider App State Enum
 *
 * Represents the current state of the rider app
 */
enum RiderAppStateEnum: string
{
    /**
     * Rider is offline
     */
    case OFFLINE = 'OFFLINE';

    /**
     * Rider is online but has no active trip
     */
    case ONLINE_IDLE = 'ONLINE_IDLE';

    /**
     * Rider has an active trip
     */
    case HAS_ACTIVE_TRIP = 'HAS_ACTIVE_TRIP';
}
