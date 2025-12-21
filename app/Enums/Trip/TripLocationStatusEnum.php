<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TripLocationStatusEnum: int implements HasColor, HasIcon, HasLabel
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

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ARRIVED => 'info',
            self::PICKED_UP => 'primary',
            self::DROPPED_OFF => 'warning',
            self::COMPLETED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::ARRIVED => 'heroicon-o-map-pin',
            self::PICKED_UP => 'heroicon-o-arrow-up-on-square',
            self::DROPPED_OFF => 'heroicon-o-arrow-down-on-square',
            self::COMPLETED => 'heroicon-o-check-circle',
        };
    }
}
