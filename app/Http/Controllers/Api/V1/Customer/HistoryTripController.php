<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\History\GetPastTripDetailsAction;
use App\Actions\Api\V1\Customer\Trip\History\GetPastTripsAction;
use App\Actions\Api\V1\Customer\Trip\History\GetUpcomingTripDetailsAction;
use App\Actions\Api\V1\Customer\Trip\History\GetUpcomingTripsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Customer\Trip\History\HistoryPastTripListResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\HistoryUpcomingTripListResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\PastTripDetailsResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\UpcomingTripDetailsResource;
use App\Models\Trip;

/**
 * @tags Trip History
 */
class HistoryTripController extends Controller
{
    /**
     * Get upcoming trips list
     *
     * Returns scheduled draft trips (confirmed but not yet processed)
     *
     * @authenticated
     */
    public function upcomingTrips(GetUpcomingTripsAction $action): HistoryUpcomingTripListResource
    {
        return new HistoryUpcomingTripListResource($action(auth('customer')->id()));
    }

    /**
     * Get upcoming trip details
     *
     * Returns detailed information about a specific upcoming trip including:
     * - Scheduled time
     * - Trip type
     * - Locations
     * - Price breakdown
     * - Ride type and passenger count
     * - Accessibility requirements
     * - Waiting time (if applicable)
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function upcomingDetails(Trip $trip, GetUpcomingTripDetailsAction $action): UpcomingTripDetailsResource
    {
        return new UpcomingTripDetailsResource($action($trip->id, auth('customer')->id()));
    }

    /**
     * Get past trips list
     *
     * Returns completed and canceled trips with rider information
     *
     * @authenticated
     */
    public function pastTrips(GetPastTripsAction $action): HistoryPastTripListResource
    {
        return new HistoryPastTripListResource($action(auth('customer')->id()));
    }

    /**
     * Get past trip details
     *
     * Returns detailed information about a specific past trip including:
     * - Rider information
     * - Vehicle plate number
     * - Trip type
     * - Locations
     * - Price breakdown
     * - Ride type and passenger count
     * - Accessibility requirements
     * - Waiting time (if applicable)
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function pastDetails(Trip $trip, GetPastTripDetailsAction $action): PastTripDetailsResource
    {
        return new PastTripDetailsResource($action($trip->id, auth('customer')->id()));
    }
}
