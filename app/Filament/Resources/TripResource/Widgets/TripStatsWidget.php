<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Widgets;

use App\Enums\Trip\TripStatusEnum;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TripStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Total Orders (all trips excluding drafts and cancelled)
        $totalOrders = Trip::query()
            ->excludingDraftAndCancelled()
            ->count();

        // Active Orders (trips that are currently active)
        $activeOrders = Trip::query()
            ->activeTrips()
            ->count();

        // Completed Orders (successfully completed trips)
        $completedOrders = Trip::query()
            ->byStatus(TripStatusEnum::COMPLETED)
            ->count();

        // Completion Rate (completed trips / total trips * 100)
        $completionRate = $totalOrders > 0
            ? round(($completedOrders / $totalOrders) * 100, 1)
            : 0;

        return [
            Stat::make(trans('trips.admin.stats.total_orders'), (string) $totalOrders)
                ->description(trans('trips.admin.stats.total_orders_description'))
                ->icon('heroicon-o-map')
                ->color('success'),

            Stat::make(trans('trips.admin.stats.active_orders'), (string) $activeOrders)
                ->description(trans('trips.admin.stats.active_orders_description'))
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(trans('trips.admin.stats.completed_orders'), (string) $completedOrders)
                ->description(trans('trips.admin.stats.completed_orders_description'))
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make(trans('trips.admin.stats.completion_rate'), $completionRate . '%')
                ->description(trans('trips.admin.stats.completion_rate_description'))
                ->icon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}
