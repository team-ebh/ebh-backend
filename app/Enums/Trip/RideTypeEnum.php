<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RideTypeEnum: int implements HasDescription, HasIcon, HasLabel
{
    case ONE_WAY = 1;
    case ROUND_TRIP = 2;
    case ROUND_TRIP_WAIT = 3;

    public function getLabel(): ?string
    {
        return trans('trips.api.ride_types.' . $this->name);
    }

    public function getDescription(): ?string
    {
        return trans('trips.api.ride_types.' . $this->name . '_description');
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ONE_WAY => asset('images/trip/ride_types/one-way.svg'),
            self::ROUND_TRIP => asset('images/trip/ride_types/round-trip.svg'),
            self::ROUND_TRIP_WAIT => asset('images/trip/ride_types/round-trip-with-wait.svg'),
        };
    }

    public static function getDefault(): self
    {
        return self::ONE_WAY;
    }

    public function isDefault(): bool
    {
        return $this === self::getDefault();
    }
}
