<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Earnings;

use App\DTOs\Api\V1\Rider\Earnings\GetEarningsReportDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\EarningsFilterEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Earnings\RiderEarningsRepositoryInterface;

/**
 * Get Earnings Report Action
 *
 * Generates comprehensive earnings report for riders including:
 * - Current period earnings summary
 * - Comparison with previous period
 * - Performance metrics (rides, hours, averages)
 */
readonly class GetEarningsReportAction
{
    private const int PERCENTAGE_FULL_GROWTH = 100;

    private const int PERCENTAGE_NO_CHANGE = 0;

    private const string DIRECTION_UP = 'up';

    private const string DIRECTION_DOWN = 'down';

    private const string DIRECTION_SAME = 'same';

    public function __construct(
        private RiderEarningsRepositoryInterface $riderEarningsRepository,
    ) {}

    /**
     * Execute earnings report generation
     */
    public function __invoke(GetEarningsReportDTO $dto): array
    {
        $currentPeriodSummary = $this->getCurrentPeriodSummary($dto);
        $previousPeriodEarnings = $this->getPreviousPeriodEarnings($dto);

        $changePercentage = $this->calculateChangePercentage(
            $currentPeriodSummary['total_earnings'],
            $previousPeriodEarnings
        );

        return $this->buildEarningsReport(
            $currentPeriodSummary,
            $changePercentage,
            $dto->filter
        );
    }

    /**
     * Get earnings summary for current period
     */
    private function getCurrentPeriodSummary(GetEarningsReportDTO $dto): array
    {
        return $this->riderEarningsRepository->getEarningsSummary($dto->riderId, $dto->filter);
    }

    /**
     * Get earnings amount from previous period for comparison
     */
    private function getPreviousPeriodEarnings(GetEarningsReportDTO $dto): float
    {
        $previousPeriod = $this->riderEarningsRepository->getPreviousPeriodEarnings($dto->riderId, $dto->filter);

        return $previousPeriod['total_earnings'];
    }

    /**
     * Build complete earnings report array
     */
    private function buildEarningsReport(
        array $summary,
        int $changePercentage,
        EarningsFilterEnum $filter
    ): array {
        $changeDirection = $this->determineChangeDirection($changePercentage);

        return [
            'total_earnings' => $summary['total_earnings'],
            'currency' => CurrencyEnum::KWD->getLabel(),
            'change_percentage' => abs($changePercentage),
            'change_direction' => $changeDirection,
            'comparison_text' => $this->buildComparisonText($changePercentage, $changeDirection, $filter),
            'total_rides' => $summary['total_rides'],
            'total_minutes' => $summary['total_minutes'],
            'average_per_ride' => $this->calculateAveragePerRide($summary['total_earnings'], $summary['total_rides']),
            'is_empty' => $this->isEmptyReport($summary['total_rides']),
        ];
    }

    /**
     * Calculate percentage change between current and previous period
     *
     * Returns 100% for growth from zero, 0% for no previous data
     */
    private function calculateChangePercentage(float $currentEarnings, float $previousEarnings): int
    {
        if ($this->hasPreviousPeriodWithNoEarnings($previousEarnings)) {
            return $this->hasCurrentEarnings($currentEarnings)
                ? self::PERCENTAGE_FULL_GROWTH
                : self::PERCENTAGE_NO_CHANGE;
        }

        return $this->computePercentageChange($currentEarnings, $previousEarnings);
    }

    /**
     * Check if previous period exists but has no earnings
     */
    private function hasPreviousPeriodWithNoEarnings(float $previousEarnings): bool
    {
        return $previousEarnings === 0.0;
    }

    /**
     * Check if current period has earnings
     */
    private function hasCurrentEarnings(float $currentEarnings): bool
    {
        return $currentEarnings > 0;
    }

    /**
     * Compute percentage change using standard formula
     */
    private function computePercentageChange(float $current, float $previous): int
    {
        $change = ($current - $previous) / $previous;
        $percentage = $change * 100;

        return (int) round($percentage);
    }

    /**
     * Determine direction of change (up, down, or same)
     */
    private function determineChangeDirection(int $percentage): string
    {
        return match (true) {
            $percentage > 0 => self::DIRECTION_UP,
            $percentage < 0 => self::DIRECTION_DOWN,
            default => self::DIRECTION_SAME,
        };
    }

    /**
     * Calculate average earnings per ride
     */
    private function calculateAveragePerRide(float $totalEarnings, int $totalRides): float
    {
        if ($this->hasNoRides($totalRides)) {
            return 0.0;
        }

        return round($totalEarnings / $totalRides, 2);
    }

    /**
     * Check if report has no rides
     */
    private function hasNoRides(int $totalRides): bool
    {
        return $totalRides === 0;
    }

    /**
     * Check if earnings report is empty (no rides)
     */
    private function isEmptyReport(int $totalRides): bool
    {
        return $this->hasNoRides($totalRides);
    }

    /**
     * Build human-readable comparison text
     */
    private function buildComparisonText(
        int $percentage,
        string $direction,
        EarningsFilterEnum $filter
    ): string {
        if ($this->hasNoChange($percentage)) {
            return $this->getNoChangeText();
        }

        return $this->getChangeText($percentage, $direction, $filter);
    }

    /**
     * Check if there's no change in earnings
     */
    private function hasNoChange(int $percentage): bool
    {
        return $percentage === self::PERCENTAGE_NO_CHANGE;
    }

    /**
     * Get translation for no change scenario
     */
    private function getNoChangeText(): string
    {
        return trans('riders.api.earnings.comparison.no_change');
    }

    /**
     * Get formatted comparison text with percentage and period
     */
    private function getChangeText(int $percentage, string $direction, EarningsFilterEnum $filter): string
    {
        return trans('riders.api.earnings.comparison.text', [
            'prefix' => $this->getChangePrefix($direction),
            'percentage' => abs($percentage),
            'period' => $this->getComparisonPeriodLabel($filter),
        ]);
    }

    /**
     * Get prefix symbol for change direction (+ or -)
     */
    private function getChangePrefix(string $direction): string
    {
        return $direction === self::DIRECTION_UP ? '+' : '-';
    }

    /**
     * Get translated label for comparison period
     */
    private function getComparisonPeriodLabel(EarningsFilterEnum $filter): string
    {
        $periodKey = $this->getComparisonPeriodKey($filter);

        return trans("riders.api.earnings.comparison.periods.{$periodKey}");
    }

    /**
     * Get comparison period key based on filter
     */
    private function getComparisonPeriodKey(EarningsFilterEnum $filter): string
    {
        return $filter === EarningsFilterEnum::TODAY ? 'yesterday' : 'last_week';
    }
}
