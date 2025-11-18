<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Location\UpdateLocationAction;
use App\DTOs\Api\V1\Rider\Location\UpdateLocationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Location\UpdateLocationRequest;
use Illuminate\Http\JsonResponse;

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
        UpdateLocationDTO     $dto,
        UpdateLocationAction  $action
    ): JsonResponse
    {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }
}
