<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripRequestStatusEnum: int implements HasLabel
{
    case PENDING = 1;
    case ACCEPTED = 2;
    case DECLINED = 3;
    case EXPIRED = 4;
    case CANCELLED = 5;
    case LOCKED = 6;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_request_statuses.' . $this->name);
    }
}
