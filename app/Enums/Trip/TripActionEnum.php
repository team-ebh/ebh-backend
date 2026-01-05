<?php

declare(strict_types=1);

namespace App\Enums\Trip;

/**
 * Trip Action Enum
 *
 * Represents the next action that can be performed on a trip
 */
enum TripActionEnum: string
{
    case ARRIVED = 'arrived';
    case PICKUP = 'pickup';
    case COMPLETE = 'complete';
}
