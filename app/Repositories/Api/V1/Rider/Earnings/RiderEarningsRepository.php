<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider\Earnings;

use App\Enums\Rider\EarningsFilterEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Rider\Earnings\RiderEarningsRepositoryInterface;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

readonly class RiderEarningsRepository implements RiderEarningsRepositoryInterface
{
    /**
     * Get total earnings for a rider based on filter
     *
     * @return array{total_earnings: float, total_rides: int, total_minutes: int}
     */
    public function getEarningsSummary(int $riderId, EarningsFilterEnum $filter): array
    {
        $query = $this->getBaseEarningsQuery($riderId);
        $this->applyDateFilter($query, $filter);

        $result = $query->selectRaw('
            COALESCE(SUM(total_price - COALESCE(commission_amount, 0)), 0) as total_earnings,
            COUNT(*) as total_rides,
            COALESCE(SUM(duration_minutes), 0) as total_minutes
        ')->first();

        return [
            'total_earnings' => (float) ($result->total_earnings ?? 0),
            'total_rides' => (int) ($result->total_rides ?? 0),
            'total_minutes' => (int) ($result->total_minutes ?? 0),
        ];
    }

    /**
     * Get earnings for comparison period (previous period based on filter)
     *
     * @return array{total_earnings: float}
     */
    public function getPreviousPeriodEarnings(int $riderId, EarningsFilterEnum $filter): array
    {
        $query = $this->getBaseEarningsQuery($riderId);
        $this->applyPreviousPeriodFilter($query, $filter);

        $result = $query->selectRaw('
            COALESCE(SUM(total_price - COALESCE(commission_amount, 0)), 0) as total_earnings
        ')->first();

        return [
            'total_earnings' => (float) ($result->total_earnings ?? 0),
        ];
    }

    /**
     * Get latest completed trips for earnings with pagination
     *
     * Selects only required columns for optimal performance
     */
    public function getLatestTrips(int $riderId): CursorPaginator
    {
        return Trip::query()
            ->select([
                Trip::COLUMN_ID,
                Trip::COLUMN_RIDER_ID,
                Trip::COLUMN_CREATED_AT,
                Trip::COLUMN_TOTAL_PRICE,
                Trip::COLUMN_COMMISSION_AMOUNT,
                Trip::COLUMN_CURRENCY,
            ])
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->completed()
            ->with([
                'locations:id,trip_id,location_title,location_sub_title',
            ])
            ->orderByDesc(Trip::COLUMN_ID)
            ->cursorPaginate(5);
    }

    /**
     * Get base query for earnings calculations (completed trips only)
     */
    private function getBaseEarningsQuery(int $riderId): Builder
    {
        return Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED);
    }

    /**
     * Apply date filter based on earnings filter enum
     */
    private function applyDateFilter(Builder $query, EarningsFilterEnum $filter): void
    {
        $kuwaitNow = Carbon::now('Asia/Kuwait');

        match ($filter) {
            EarningsFilterEnum::TODAY => $query->whereDate(Trip::COLUMN_COMPLETED_AT, $kuwaitNow->toDateString()),
            EarningsFilterEnum::THIS_WEEK => $query->whereBetween(
                Trip::COLUMN_COMPLETED_AT,
                [$kuwaitNow->copy()->startOfWeek(), $kuwaitNow->copy()->endOfWeek()]
            ),
            EarningsFilterEnum::PAST_TRIPS => null, // No date filter for past trips - show all
        };
    }

    /**
     * Apply previous period filter for comparison
     */
    private function applyPreviousPeriodFilter(Builder $query, EarningsFilterEnum $filter): void
    {
        $kuwaitNow = Carbon::now('Asia/Kuwait');

        match ($filter) {
            EarningsFilterEnum::TODAY => $query->whereDate(Trip::COLUMN_COMPLETED_AT, $kuwaitNow->copy()->subDay()->toDateString()),
            EarningsFilterEnum::THIS_WEEK => $query->whereBetween(
                Trip::COLUMN_COMPLETED_AT,
                [$kuwaitNow->copy()->subWeek()->startOfWeek(), $kuwaitNow->copy()->subWeek()->endOfWeek()]
            ),
            EarningsFilterEnum::PAST_TRIPS => null,
        };
    }
}
