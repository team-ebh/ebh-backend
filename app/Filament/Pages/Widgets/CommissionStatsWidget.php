<?php

declare(strict_types=1);

namespace App\Filament\Pages\Widgets;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Trip;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CommissionStatsWidget extends BaseWidget
{
    private const int DECIMAL_PLACES = 3;

    protected function getStats(): array
    {
        return [
            $this->buildTotalCommissionStat(),
            $this->buildCustomRatesCompanyStat(),
            $this->buildAverageRateStat(),
            $this->buildThisMonthCommissionStat(),
        ];
    }

    private function buildTotalCommissionStat(): Stat
    {
        $total = (float) ($this->completedTripsQuery()->sum(Trip::COLUMN_COMMISSION_AMOUNT) ?? 0);
        $previousTotal = (float) ($this->completedTripsQuery()
            ->where(Trip::COLUMN_CREATED_AT, '<', Carbon::now()->startOfMonth())
            ->sum(Trip::COLUMN_COMMISSION_AMOUNT) ?? 0);

        $change = $this->calculatePercentageChange($total, $previousTotal);

        return Stat::make(
            trans('commission_settings.admin.stats.total_commission'),
            $this->formatCurrency($total)
        )
            ->description($this->formatPercentage($change))
            ->descriptionIcon($this->getTrendIcon($change))
            ->color($this->getTrendColor($change))
            ->chart([7, 3, 4, 5, 6, 3, 5, 3]);
    }

    private function buildCustomRatesCompanyStat(): Stat
    {
        $count = Company::query()
            ->where(Company::COLUMN_IS_CUSTOM_RATE, true)
            ->count();

        return Stat::make(
            trans('commission_settings.admin.stats.companies_custom_rates'),
            (string) $count
        )
            ->description(trans('commission_settings.admin.stats.companies'))
            ->descriptionIcon('heroicon-m-building-office-2')
            ->color('warning');
    }

    private function buildAverageRateStat(): Stat
    {
        $avgRate = Company::query()->avg(Company::COLUMN_COMMISSION_RATE)
            ?? Setting::getDefaultCommissionRate();

        return Stat::make(
            trans('commission_settings.admin.stats.avg_rate'),
            round((float) $avgRate, 1) . '%'
        )
            ->description(trans('commission_settings.admin.stats.average'))
            ->descriptionIcon('heroicon-m-chart-pie')
            ->color('info');
    }

    private function buildThisMonthCommissionStat(): Stat
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonth = (float) ($this->completedTripsQuery()
            ->where(Trip::COLUMN_CREATED_AT, '>=', $currentMonth)
            ->sum(Trip::COLUMN_COMMISSION_AMOUNT) ?? 0);

        $lastMonth = (float) ($this->completedTripsQuery()
            ->whereBetween(Trip::COLUMN_CREATED_AT, [$lastMonthStart, $lastMonthEnd])
            ->sum(Trip::COLUMN_COMMISSION_AMOUNT) ?? 0);

        $change = $this->calculatePercentageChange($thisMonth, $lastMonth);

        return Stat::make(
            trans('commission_settings.admin.stats.this_month'),
            $this->formatCurrency($thisMonth)
        )
            ->description($this->formatPercentage($change))
            ->descriptionIcon($this->getTrendIcon($change))
            ->color($this->getTrendColor($change))
            ->chart([3, 5, 4, 6, 7, 4, 6, 5]);
    }

    private function completedTripsQuery(): Builder
    {
        return Trip::query()->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED);
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function formatCurrency(float $amount): string
    {
        return CurrencyEnum::KWD->getLabel() . ' ' . number_format($amount, self::DECIMAL_PLACES);
    }

    private function formatPercentage(float $value): string
    {
        return ($value >= 0 ? '+' : '') . $value . '%';
    }

    private function getTrendIcon(float $change): string
    {
        return $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getTrendColor(float $change): string
    {
        return $change >= 0 ? 'success' : 'danger';
    }
}
