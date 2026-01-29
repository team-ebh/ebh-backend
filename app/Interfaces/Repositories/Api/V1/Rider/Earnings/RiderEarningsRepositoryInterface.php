<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Rider\Earnings;

use App\Enums\Rider\EarningsFilterEnum;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface RiderEarningsRepositoryInterface
{
    /**
     * Get total earnings for a rider based on filter
     *
     * @return array{total_earnings: float, total_rides: int, total_minutes: int}
     */
    public function getEarningsSummary(int $riderId, EarningsFilterEnum $filter): array;

    /**
     * Get earnings for comparison period (previous period based on filter)
     *
     * @return array{total_earnings: float}
     */
    public function getPreviousPeriodEarnings(int $riderId, EarningsFilterEnum $filter): array;

    /**
     * Get latest completed trips for earnings with pagination
     */
    public function getLatestTrips(int $riderId): CursorPaginator;
}
