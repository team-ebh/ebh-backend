<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\CancelTripAction;
use App\Actions\Api\V1\Customer\Trip\ChangeRideTypeAction;
use App\Actions\Api\V1\Customer\Trip\ConfirmTripAction;
use App\Actions\Api\V1\Customer\Trip\GetActiveTripAction;
use App\Actions\Api\V1\Customer\Trip\GetEstimatedArrivalTimeAction;
use App\Actions\Api\V1\Customer\Trip\GetRiderLocationAction;
use App\Actions\Api\V1\Customer\Trip\GetTripFormDataAction;
use App\Actions\Api\V1\Customer\Trip\GetTripStatusAction;
use App\Actions\Api\V1\Customer\Trip\StoreTripAction;
use App\DTOs\Api\V1\Customer\Trip\CancelTripDTO;
use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;
use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\DTOs\Api\V1\Customer\Trip\GetActiveTripDTO;
use App\DTOs\Api\V1\Customer\Trip\GetEstimatedArrivalTimeDTO;
use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Trip\ChangeRideTypeRequest;
use App\Http\Requests\Api\V1\Customer\Trip\TripStoreRequest;
use App\Http\Resources\Api\V1\Customer\Trip\ChangeRideTypeResource;
use App\Http\Resources\Api\V1\Customer\Trip\EstimatedArrivalTimeResource;
use App\Http\Resources\Api\V1\Customer\Trip\RiderLocationResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripFormDataResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripStatusResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Trip
 */
class TripController extends Controller
{
    /**
     * Get all form data for trip creation
     *
     * @unauthenticated
     */
    public function formData(GetTripFormDataAction $action): TripFormDataResource
    {
        return new TripFormDataResource($action());
    }

    /**
     * Store a new trip booking
     *
     * Creates a new trip with reverse geocoded location information from coordinates.
     * Calculates base fare and estimated price based on distance and preferences.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function store(
        TripStoreRequest $request,
        TripStoreDTO $dto,
        StoreTripAction $action
    ): TripResource {
        $dto->getDataFromRequest($request);

        return new TripResource($action($dto));
    }

    /**
     * Change ride type
     *
     * Calculates price estimation and breakdown for different ride types without creating a trip.
     * Supports ONE_WAY, ROUND_TRIP, and ROUND_TRIP_WAIT with waiting time calculation.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function changeRideType(
        Trip $trip,
        ChangeRideTypeRequest $request,
        ChangeRideTypeDTO $dto,
        ChangeRideTypeAction $action
    ): ChangeRideTypeResource {
        $dto->getDataFromRequest($request);

        return new ChangeRideTypeResource($action($dto));
    }

    /**
     * Confirm trip
     *
     * Confirms the trip and changes status to CONFIRMED (searching for taxi).
     * After confirmation, customer should poll getTripStatus endpoint to track the driver.
     *
     * @authenticated
     */
    public function confirm(
        Trip $trip,
        Request $request,
        ConfirmTripDTO $dto,
        ConfirmTripAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }

    /**
     * Get customer's active trip
     *
     * Returns the current active trip information for the customer (not completed/cancelled).
     * Returns null if customer has no active trip.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function activeTrip(
        Request $request,
        GetActiveTripDTO $dto,
        GetActiveTripAction $action,
    ): TripStatusResource | JsonResponse {
        $dto->getDataFromRequest($request);
        $activeTrip = $action($dto);

        if (is_null($activeTrip)) {
            return response()->json(['data' => null]);
        }

        return new TripStatusResource($activeTrip);
    }

    /**
     * Get trip status
     *
     * Returns comprehensive trip status including rider details, vehicle information, and location history.
     * Customer app should poll this endpoint every few seconds after confirming trip to track the driver.
     *
     * @authenticated
     */
    public function getTripStatus(Trip $trip, GetTripStatusAction $action): TripStatusResource
    {
        return new TripStatusResource($action($trip));
    }

    /**
     * Cancel trip
     *
     * Cancels the trip and changes status to CANCEL.
     * Only PENDING_RIDER or ACCEPTED_RIDER trips can be cancelled.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function cancel(
        Trip $trip,
        Request $request,
        CancelTripDTO $dto,
        CancelTripAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }

    /**
     * Get rider location
     *
     * Returns the current location of the rider assigned to the trip.
     * Only available when trip has status ACCEPTED_RIDER, ARRIVED, or PICKED_UP.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function getRiderLocation(Trip $trip, GetRiderLocationAction $action): RiderLocationResource
    {
        return new RiderLocationResource($action($trip));
    }

    /**
     * Get estimated arrival time to next destination
     *
     * Returns the estimated time in seconds for the rider to reach the next destination (origin or destination location).
     * This endpoint should be called every 60 seconds while the rider is moving towards the next location.
     * The customer should call this API when:
     * - Rider is moving towards an origin location (to pick up passenger)
     * - Rider is moving towards a destination location (after picking up passenger)
     *
     * The rider's location is automatically retrieved from the database, no need to send location data in the request.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function estimatedArrivalTime(
        Trip $trip,
        Request $request,
        GetEstimatedArrivalTimeDTO $dto,
        GetEstimatedArrivalTimeAction $action,
    ): EstimatedArrivalTimeResource {
        $dto->getDataFromRequest($request);

        return new EstimatedArrivalTimeResource($action($dto));
    }
}
