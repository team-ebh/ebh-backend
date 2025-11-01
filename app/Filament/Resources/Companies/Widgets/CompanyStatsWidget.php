<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Widgets;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Company;
use App\Models\Rider;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CompanyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCompanies = Company::query()->count();
        $activeCompanies = Company::enabled()->count();
        $totalRiders = Rider::query()->count();

        // Calculate total earnings from trips
        $totalEarnings = (float) (Trip::query()->byStatus(TripStatusEnum::COMPLETED)->sum(Trip::COLUMN_TOTAL_PRICE) ?? 0);

        // Format earnings to match format like "KWD 46.8K"
        $earningsDisplay = $this->formatEarnings($totalEarnings);

        return [
            Stat::make(trans('companies.admin.stats.total_companies'), (string) $totalCompanies)
                ->description(trans('companies.admin.stats.total_companies_description'))
                ->icon('heroicon-o-building-office-2'),

            Stat::make(trans('companies.admin.stats.active_companies'), (string) $activeCompanies)
                ->description(trans('companies.admin.stats.active_companies_description'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(trans('companies.admin.stats.total_riders'), (string) $totalRiders)
                ->description(trans('companies.admin.stats.total_riders_description'))
                ->icon('heroicon-o-users'),

            Stat::make(trans('companies.admin.stats.total_earnings'), $earningsDisplay)
                ->description(trans('companies.admin.stats.total_earnings_description'))
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }

    private function formatEarnings(float $amount): string
    {
        if ($amount >= 1000000) {
            // Millions: KWD 1.5M
            return CurrencyEnum::KWD->getLabel() . ' ' . Number::format($amount / 1000000, precision: 1) . 'M';
        } elseif ($amount >= 1000) {
            // Thousands: KWD 46.8K
            return CurrencyEnum::KWD->getLabel() . ' ' . Number::format($amount / 1000, precision: 1) . 'K';
        } else {
            // Less than 1000: KWD 999.99
            return CurrencyEnum::KWD->getLabel() . ' ' . priceFormat($amount);
        }
    }
}
