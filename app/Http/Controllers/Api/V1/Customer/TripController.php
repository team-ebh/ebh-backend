<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\ChangeRideTypeAction;
use App\Actions\Api\V1\Customer\Trip\GetTripFormDataAction;
use App\Actions\Api\V1\Customer\Trip\StoreTripAction;
use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;
use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Trip\ChangeRideTypeRequest;
use App\Http\Requests\Api\V1\Customer\Trip\TripStoreRequest;
use App\Http\Resources\Api\V1\Customer\Trip\ChangeRideTypeResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripFormDataResource;
use App\Http\Resources\Api\V1\Customer\Trip\TripResource;
use App\Models\Trip;

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
     * Calculate ride pricing based on ride type
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
}
