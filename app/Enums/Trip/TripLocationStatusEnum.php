<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripLocationStatusEnum: int implements HasLabel
{
    case PENDING = 1;
    case ARRIVED = 2;
    case PICKED_UP = 3;
    case DROPPED_OFF = 4;
    case COMPLETED = 5;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_location_statuses.' . $this->name);
    }
}
