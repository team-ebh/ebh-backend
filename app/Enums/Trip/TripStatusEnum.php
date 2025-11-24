<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripStatusEnum: int implements HasLabel
{
    case DRAFT = 1;
    case PENDING_RIDER = 2;
    case ACCEPTED_RIDER = 3;
    case ARRIVED = 4;
    case PICKUP = 5;
    case COMPLETED = 6;
    case CANCELED_BY_CUSTOMER = 7;
    case CANCELLED_BY_RIDER = 8;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_statuses.' . $this->name);
    }
}
