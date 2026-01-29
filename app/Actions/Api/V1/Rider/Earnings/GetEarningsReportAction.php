<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Earnings;

use App\DTOs\Api\V1\Rider\Earnings\GetEarningsReportDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Enums\Rider\EarningsFilterEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Earnings\RiderEarningsRepositoryInterface;

readonly class GetEarningsReportAction
{
    public function __construct(
        private RiderEarningsRepositoryInterface $riderEarningsRepository,
    ) {}

    public function __invoke(GetEarningsReportDTO $dto): array
    {
        $summary = $this->riderEarningsRepository->getEarningsSummary($dto->riderId, $dto->filter);
        $previousPeriod = $this->riderEarningsRepository->getPreviousPeriodEarnings($dto->riderId, $dto->filter);

        $changePercentage = $this->calculateChangePercentage($summary['total_earnings'], $previousPeriod['total_earnings']);
        $changeDirection = $this->getChangeDirection($changePercentage);

        return [
            'total_earnings' => $summary['total_earnings'],
            'currency' => CurrencyEnum::KWD->getLabel(),
            'change_percentage' => abs($changePercentage),
            'change_direction' => $changeDirection,
            'comparison_text' => $this->getComparisonText($changePercentage, $changeDirection, $dto->filter),
            'total_rides' => $summary['total_rides'],
            'total_minutes' => $summary['total_minutes'],
            'average_per_ride' => $summary['total_rides'] > 0
                ? round($summary['total_earnings'] / $summary['total_rides'], 2)
                : 0.0,
            'is_empty' => $summary['total_rides'] === 0,
        ];
    }

    private function calculateChangePercentage(float $current, float $previous): int
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function getChangeDirection(int $percentage): string
    {
        return match (true) {
            $percentage > 0 => 'up',
            $percentage < 0 => 'down',
            default => 'same',
        };
    }

    private function getComparisonText(int $percentage, string $direction, EarningsFilterEnum $filter): string
    {
        if ($percentage === 0) {
            return trans('riders.api.earnings.comparison.no_change');
        }

        $periodKey = $filter === EarningsFilterEnum::TODAY ? 'yesterday' : 'last_week';

        return trans('riders.api.earnings.comparison.text', [
            'prefix' => $direction === 'up' ? '+' : '-',
            'percentage' => abs($percentage),
            'period' => trans("riders.api.earnings.comparison.periods.{$periodKey}"),
        ]);
    }
}
