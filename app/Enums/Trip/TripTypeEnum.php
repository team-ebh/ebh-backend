<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripTypeEnum: int implements HasLabel
{
    case RIDE_NOW = 1;
    case SCHEDULED = 2;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_types.' . $this->name);
    }
}
