<?php

declare(strict_types=1);

namespace App\Enums\Trip;

enum TripHistoryFilterEnum: string
{
    case ALL = 'all';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return trans('trips.api.filters.' . $this->value);
    }

    public function isDefault(): bool
    {
        return $this === self::ALL;
    }

    /**
     * Get all filters with their labels and default status
     *
     * @return array<int, array{id: string, label: string, is_default: bool}>
     */
    public static function getFiltersArray(): array
    {
        return array_map(
            fn (self $filter) => [
                'id' => $filter->value,
                'label' => $filter->getLabel(),
                'is_default' => $filter->isDefault(),
            ],
            self::cases()
        );
    }
}
