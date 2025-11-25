<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Trip\AcceptTripRequestAction;
use App\Actions\Api\V1\Rider\Trip\ArriveTripAction;
use App\Actions\Api\V1\Rider\Trip\CancelTripAction;
use App\Actions\Api\V1\Rider\Trip\CompleteTripAction;
use App\Actions\Api\V1\Rider\Trip\DeclineTripRequestAction;
use App\Actions\Api\V1\Rider\Trip\GetActiveTripAction;
use App\Actions\Api\V1\Rider\Trip\GetEstimatedArrivalTimeAction;
use App\Actions\Api\V1\Rider\Trip\GetTripRequestsAction;
use App\Actions\Api\V1\Rider\Trip\PickUpTripAction;
use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\DTOs\Api\V1\Rider\Trip\ArrivedTripDTO;
use App\DTOs\Api\V1\Rider\Trip\CancelTripDTO;
use App\DTOs\Api\V1\Rider\Trip\CompleteTripDTO;
use App\DTOs\Api\V1\Rider\Trip\DeclineTripRequestDTO;
use App\DTOs\Api\V1\Rider\Trip\GetActiveTripDTO;
use App\DTOs\Api\V1\Rider\Trip\GetEstimatedArrivalTimeDTO;
use App\DTOs\Api\V1\Rider\Trip\GetTripRequestsDTO;
use App\DTOs\Api\V1\Rider\Trip\PickUpTripDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rider\Trip\AcceptTripRequestResource;
use App\Http\Resources\Api\V1\Rider\Trip\EstimatedArrivalTimeResource;
use App\Http\Resources\Api\V1\Rider\Trip\TripActionResource;
use App\Http\Resources\Api\V1\Rider\Trip\TripRequestResource;
use App\Models\TripRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Trip
 */
class TripController extends Controller
{
    /**
     * Get available trip requests for rider
     *
     * Returns list of pending trip requests that the rider can accept
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function requests(
        Request $request,
        GetTripRequestsDTO $dto,
        GetTripRequestsAction $action
    ): AnonymousResourceCollection {
        $dto->getDataFromRequest($request);

        return TripRequestResource::collection($action($dto));
    }

    /**
     * Get rider's active trip
     *
     * Returns the current active trip information for the rider (accepted, arrived, or picked up status)
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function activeTrip(
        Request $request,
        GetActiveTripDTO $dto,
        GetActiveTripAction $action,
    ): AcceptTripRequestResource {
        $dto->getDataFromRequest($request);

        return new AcceptTripRequestResource($action($dto));
    }

    /**
     * Accept a trip request
     *
     * Allows rider to accept a trip request
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function accept(
        TripRequest $tripRequest,
        Request $request,
        AcceptTripRequestDTO $dto,
        AcceptTripRequestAction $action,
    ): AcceptTripRequestResource {
        $dto->getDataFromRequest($request);

        return new AcceptTripRequestResource($action($dto));
    }

    /**
     * Decline a trip request
     *
     * Allows rider to decline a trip request
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function decline(
        TripRequest $tripRequest,
        Request $request,
        DeclineTripRequestDTO $dto,
        DeclineTripRequestAction $action,
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }

    /**
     * Cancel an accepted trip
     *
     * Allows rider to cancel a trip they have accepted
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function cancel(
        TripRequest $tripRequest,
        Request $request,
        CancelTripDTO $dto,
        CancelTripAction $action,
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }

    /**
     * Mark rider as arrived at location
     *
     * Updates current location status to arrived and returns next action
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function arrivedTripLocation(
        TripRequest $tripRequest,
        Request $request,
        ArrivedTripDTO $dto,
        ArriveTripAction $action,
    ): TripActionResource {
        $dto->getDataFromRequest($request);

        return new TripActionResource($action($dto));
    }

    /**
     * Mark passenger as picked up
     *
     * Updates current location status to picked up and returns next action
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function pickUpPassenger(
        TripRequest $tripRequest,
        Request $request,
        PickUpTripDTO $dto,
        PickUpTripAction $action,
    ): TripActionResource {
        $dto->getDataFromRequest($request);

        return new TripActionResource($action($dto));
    }

    /**
     * Complete current location
     *
     * Marks current location as completed. If all locations completed, marks trip as completed and rider as online
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function completeTripLocation(
        TripRequest $tripRequest,
        Request $request,
        CompleteTripDTO $dto,
        CompleteTripAction $action,
    ): TripActionResource {
        $dto->getDataFromRequest($request);

        return new TripActionResource($action($dto));
    }

    /**
     * Get estimated arrival time to next destination
     *
     * Returns the estimated time in seconds for the rider to reach the next destination (origin or destination location).
     * This endpoint should be called every 60 seconds while the rider is moving towards the next location.
     * The rider should call this API when:
     * - Moving towards an origin location (to pick up passenger)
     * - Moving towards a destination location (after picking up passenger)
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function estimatedArrivalTime(
        TripRequest $tripRequest,
        Request $request,
        GetEstimatedArrivalTimeDTO $dto,
        GetEstimatedArrivalTimeAction $action,
    ): EstimatedArrivalTimeResource {
        $dto->getDataFromRequest($request);

        return new EstimatedArrivalTimeResource($action($dto));
    }
}
