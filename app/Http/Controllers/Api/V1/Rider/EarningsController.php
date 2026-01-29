<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Earnings\GetEarningsFiltersAction;
use App\Actions\Api\V1\Rider\Earnings\GetEarningsLatestTripsAction;
use App\Actions\Api\V1\Rider\Earnings\GetEarningsReportAction;
use App\DTOs\Api\V1\Rider\Earnings\GetEarningsLatestTripsDTO;
use App\DTOs\Api\V1\Rider\Earnings\GetEarningsReportDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Earnings\GetEarningsReportRequest;
use App\Http\Resources\Api\V1\Rider\Earnings\EarningsFiltersResource;
use App\Http\Resources\Api\V1\Rider\Earnings\EarningsLatestTripsResource;
use App\Http\Resources\Api\V1\Rider\Earnings\EarningsReportResource;
use Illuminate\Http\Request;

/**
 * @tags Earnings
 */
class EarningsController extends Controller
{
    /**
     * Get earnings filters
     *
     * Returns available filter options for the earnings tab:
     * - today: Shows today's earnings
     * - this_week: Shows current week's earnings
     * - past_trips: Shows all past trip earnings (with pagination)
     *
     * @authenticated
     */
    public function filters(GetEarningsFiltersAction $action): EarningsFiltersResource
    {
        return new EarningsFiltersResource($action());
    }

    /**
     * Get earnings report
     *
     * Returns earnings summary based on selected filter including:
     * - Total earnings amount
     * - Percentage change from previous period
     * - Total rides count
     * - Total hours worked
     * - Average earning per ride
     *
     * @authenticated
     */
    public function report(
        GetEarningsReportRequest $request,
        GetEarningsReportDTO $dto,
        GetEarningsReportAction $action
    ): EarningsReportResource {
        $dto->getDataFromRequest($request);

        return new EarningsReportResource($action($dto));
    }

    /**
     * Get latest trips for earnings
     *
     * Returns the most recent completed trips with fare information.
     * Uses cursor-based pagination for infinite scroll (5 per page).
     *
     * @authenticated
     */
    public function latestTrips(
        Request $request,
        GetEarningsLatestTripsDTO $dto,
        GetEarningsLatestTripsAction $action
    ): EarningsLatestTripsResource {
        $dto->getDataFromRequest($request);

        return new EarningsLatestTripsResource($action($dto));
    }
}
