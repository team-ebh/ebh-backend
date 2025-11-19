<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Trip\AcceptTripRequestAction;
use App\Actions\Api\V1\Rider\Trip\CancelTripAction;
use App\Actions\Api\V1\Rider\Trip\DeclineTripRequestAction;
use App\Actions\Api\V1\Rider\Trip\GetActiveTripAction;
use App\Actions\Api\V1\Rider\Trip\GetTripRequestsAction;
use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\DTOs\Api\V1\Rider\Trip\CancelTripDTO;
use App\DTOs\Api\V1\Rider\Trip\DeclineTripRequestDTO;
use App\DTOs\Api\V1\Rider\Trip\GetActiveTripDTO;
use App\DTOs\Api\V1\Rider\Trip\GetTripRequestsDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rider\Trip\AcceptTripRequestResource;
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
}
