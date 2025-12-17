<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Widgets;

use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TripStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Total Orders (all trips excluding drafts and cancelled)
        $totalOrders = Trip::query()
            ->withoutDraft()
            ->count();

        // Active Orders (trips that are currently active)
        $activeOrders = Trip::query()
            ->activeTrips()
            ->count();

        // Priority Orders (scheduled trips that are active or completed)
        $priorityOrders = Trip::query()
            ->where(Trip::COLUMN_TRIP_TYPE_ID, TripTypeEnum::SCHEDULED)
            ->excludingDraftAndCancelled()
            ->count();

        // Completion Rate (completed trips / total trips * 100)
        $completedTrips = Trip::query()
            ->byStatus(TripStatusEnum::COMPLETED)
            ->count();

        $completionRate = $totalOrders > 0
            ? round(($completedTrips / $totalOrders) * 100, 1)
            : 0;

        return [
            Stat::make(trans('trips.admin.stats.total_orders'), (string) $totalOrders)
                ->description(trans('trips.admin.stats.total_orders_description'))
                ->icon('heroicon-o-map')
                ->color('primary'),

            Stat::make(trans('trips.admin.stats.active_orders'), (string) $activeOrders)
                ->description(trans('trips.admin.stats.active_orders_description'))
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(trans('trips.admin.stats.priority_orders'), (string) $priorityOrders)
                ->description(trans('trips.admin.stats.priority_orders_description'))
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make(trans('trips.admin.stats.completion_rate'), $completionRate . '%')
                ->description(trans('trips.admin.stats.completion_rate_description'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
