<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TripStatusEnum: int implements HasColor, HasIcon, HasLabel
{
    case DRAFT = 1;
    case PENDING_RIDER = 2;
    case ACCEPTED_RIDER = 3;
    case ARRIVED = 4;
    case IN_PROGRESS = 5;
    case COMPLETED = 6;
    case CANCELED_BY_CUSTOMER = 7;
    case CANCELLED_BY_RIDER = 8;

    public function getLabel(): ?string
    {
        return trans('trips.api.trip_statuses.' . $this->name);
    }

    /**
     * Get simplified label for trip history (e.g., "Canceled" instead of "Canceled by Customer")
     */
    public function getSimpleLabel(): string
    {
        return trans('trips.api.trip_statuses_simple.' . $this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING_RIDER => 'warning',
            self::ACCEPTED_RIDER => 'info',
            self::ARRIVED => 'purple',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::CANCELED_BY_CUSTOMER, self::CANCELLED_BY_RIDER => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document-text',
            self::PENDING_RIDER => 'heroicon-o-clock',
            self::ACCEPTED_RIDER => 'heroicon-o-check-circle',
            self::ARRIVED => 'heroicon-o-map-pin',
            self::IN_PROGRESS => 'heroicon-o-truck',
            self::COMPLETED => 'heroicon-o-flag',
            self::CANCELED_BY_CUSTOMER, self::CANCELLED_BY_RIDER => 'heroicon-o-x-circle',
        };
    }
}
