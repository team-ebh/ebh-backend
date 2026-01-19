<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\History\GetTripDetailsAction;
use App\Actions\Api\V1\Customer\Trip\History\GetTripsHistoryAction;
use App\DTOs\Api\V1\Customer\Trip\History\TripHistoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Trip\History\TripHistoryRequest;
use App\Http\Resources\Api\V1\Customer\Trip\History\TripDetailsResource;
use App\Http\Resources\Api\V1\Customer\Trip\History\TripHistoryResource;
use App\Models\Trip;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Trip History
 */
class HistoryTripController extends Controller
{
    /**
     * Get trip history
     *
     * Returns trips based on type:
     * - upcoming: Scheduled draft trips (confirmed but not yet processed)
     * - past: Completed and cancelled trips
     *
     * @authenticated
     */
    public function trips(
        TripHistoryRequest $request,
        TripHistoryDTO $dto,
        GetTripsHistoryAction $action
    ): AnonymousResourceCollection {
        $dto->getDataFromRequest($request);
        $trips = $action($dto);

        return TripHistoryResource::collection(
            $trips->map(fn (Trip $trip) => new TripHistoryResource($trip, $dto->type))
        );
    }

    /**
     * Get trip details
     *
     * Returns detailed information about a specific trip including:
     * - Price breakdown (base fare, round trip fee, waiting charge, accessibility, total)
     * - Ride details (ride type, passenger count, waiting time)
     * - Accessibility requirements selected for the trip
     * - Rider information (if assigned)
     * - Location details
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function details(Trip $trip, GetTripDetailsAction $action): TripDetailsResource
    {
        $customerId = auth('customer')->id();

        return new TripDetailsResource($action($trip->id, $customerId));
    }
}
