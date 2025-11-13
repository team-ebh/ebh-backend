<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\CancelTripAction;
use App\Actions\Api\V1\Customer\Trip\ChangeRideTypeAction;
use App\Actions\Api\V1\Customer\Trip\ConfirmTripAction;
use App\Actions\Api\V1\Customer\Trip\GetRiderLocationAction;
use App\Actions\Api\V1\Customer\Trip\GetTripFormDataAction;
use App\Actions\Api\V1\Customer\Trip\GetTripStatusAction;
use App\Actions\Api\V1\Customer\Trip\StoreTripAction;
use App\DTOs\Api\V1\Customer\Trip\CancelTripDTO;
use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;
use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Trip\ChangeRideTypeRequest;
use App\Http\Requests\Api\V1\Customer\Trip\TripStoreRequest;
use App\Http\Resources\Api\V1\Customer\Trip\ChangeRideTypeResource;
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
}
