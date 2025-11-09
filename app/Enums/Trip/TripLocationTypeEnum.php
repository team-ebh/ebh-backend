<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripLocationTypeEnum: int implements HasLabel
{
    case ORIGIN = 1;
    case DESTINATION = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ORIGIN => trans('trips.location_types.origin'),
            self::DESTINATION => trans('trips.location_types.destination'),
        };
    }
}
