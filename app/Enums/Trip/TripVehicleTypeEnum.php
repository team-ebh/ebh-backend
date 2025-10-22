<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum TripVehicleTypeEnum: int implements HasLabel
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
}
