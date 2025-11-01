<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Widgets;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RiderStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRiders = Rider::query()->count();
        $onlineRiders = Rider::query()->where(Rider::COLUMN_STATUS, RiderStatusEnum::ONLINE)->count();

        // Count riders with at least one accessibility certification
        $accessibilityCertified = Rider::query()
            ->whereNotNull(Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS)
            ->whereRaw('JSON_LENGTH(`' . Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS . '`) > 0')
            ->count();

        // Calculate average rating
        // Note: This assumes there's a rating system. Adjust based on your database structure
        // For now, using a placeholder calculation - you may need to join with a ratings/reviews table
        $avgRating = 4.8; // Placeholder - replace with actual calculation if rating system exists

        return [
            Stat::make(trans('riders.admin.stats.total_riders'), (string) $totalRiders)
                ->description(trans('riders.admin.stats.total_riders_description'))
                ->icon('heroicon-o-users'),

            Stat::make(trans('riders.admin.stats.online_now'), (string) $onlineRiders)
                ->description(trans('riders.admin.stats.online_now_description'))
                ->icon('heroicon-o-signal')
                ->color('success'),

            Stat::make(trans('riders.admin.stats.accessibility_certified'), (string) $accessibilityCertified)
                ->description(trans('riders.admin.stats.accessibility_certified_description'))
                ->icon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make(trans('riders.admin.stats.avg_rating'), number_format($avgRating, 1))
                ->description(trans('riders.admin.stats.avg_rating_description'))
                ->icon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}
