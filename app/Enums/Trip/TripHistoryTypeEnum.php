<?php

declare(strict_types=1);

namespace App\Enums\Trip;

enum TripHistoryTypeEnum: string
{
    case UPCOMING = 'upcoming';
    case PAST = 'past';
}
