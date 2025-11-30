<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripStatusEnum: int implements HasLabel
{
    case DRAFT = 1;
    case PENDING_RIDER = 2;
    case ACCEPTED_RIDER = 3;
    case ON_TRIP = 4;
    case COMPLETED = 5;
    case CANCELED_BY_CUSTOMER = 6;
    case CANCELLED_BY_RIDER = 7;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_statuses.' . $this->name);
    }
}
