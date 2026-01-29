<?php

declare(strict_types=1);

namespace App\Enums\Rider;

enum EarningsFilterEnum: string
{
    case TODAY = 'today';
    case THIS_WEEK = 'this_week';
    case PAST_TRIPS = 'past_trips';

    public function getLabel(): string
    {
        return trans('riders.api.earnings.filters.' . $this->value);
    }

    public function isDefault(): bool
    {
        return $this === self::TODAY;
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
