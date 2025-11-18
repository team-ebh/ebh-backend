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
    case CANCELED_BY_CUSTOMER = 5;
    case CANCELLED_BY_RIDER = 6;
    case PICKED_UP = 7;
    case COMPLETED = 8;
    case DECLINED = 9;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_statuses.' . $this->name);
    }
}
