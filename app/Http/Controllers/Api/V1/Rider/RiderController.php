<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\GetAppStateAction;
use App\Actions\Api\V1\Rider\Location\UpdateLocationAction;
use App\DTOs\Api\V1\Rider\AppStateDTO;
use App\DTOs\Api\V1\Rider\Location\UpdateLocationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Location\UpdateLocationRequest;
use App\Http\Resources\Api\V1\Rider\AppStateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Rider
 */
class RiderController extends Controller
{
    /**
     * Update rider's current location
     *
     * Updates the rider's current latitude and longitude coordinates.
     * This endpoint should be called every 15 seconds when the rider is online.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function updateLocation(
        UpdateLocationRequest $request,
        UpdateLocationDTO $dto,
        UpdateLocationAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }

    /**
     * Get current app state
     *
     * Returns the current state of the rider app to help mobile app determine its state
     *
     * @authenticated
     */
    public function appState(
        Request $request,
        AppStateDTO $dto,
        GetAppStateAction $action,
    ): AppStateResource {
        $dto->getDataFromRequest($request);

        return new AppStateResource($action($dto));
    }
}
