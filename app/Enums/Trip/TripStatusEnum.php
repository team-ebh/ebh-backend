<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripStatusEnum: int implements HasLabel
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case DRIVER_ASSIGNED = 3;
    case IN_PROGRESS = 4;
    case ARRIVED = 5;
    case COMPLETED = 6;
    case CANCELLED = 7;
    case CANCELLED_BY_DRIVER = 8;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_statuses.' . $this->name);
    }
}
