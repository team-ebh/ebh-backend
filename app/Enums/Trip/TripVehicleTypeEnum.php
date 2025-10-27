<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TripVehicleTypeEnum: int implements HasIcon, HasLabel
{
    case WHEELCHAIR_ACCESSIBLE = 1;
    case BED_TRANSPORT = 2;

    public function getLabel(): ?string
    {
        return trans('trips.api.vehicle_types.' . $this->name);
    }

    public function getDescription(): ?string
    {
        return trans('trips.api.vehicle_types.' . $this->name . '_description');
    }

    public function getIcon(): string | BackedEnum | null
    {
        return match ($this) {
            self::WHEELCHAIR_ACCESSIBLE => asset('images/trip/types/wheelchair.svg'),
            self::BED_TRANSPORT => asset('images/trip/types/ambulance.svg'),
        };
    }
}
