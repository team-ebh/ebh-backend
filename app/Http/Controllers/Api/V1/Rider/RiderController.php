<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\GetAppStateAction;
use App\Actions\Api\V1\Rider\Location\UpdateLocationAction;
use App\Actions\Api\V1\Rider\Profile\UpdateProfileAction;
use App\Actions\Api\V1\Rider\Profile\UpdateProfileImageAction;
use App\Actions\Api\V1\Rider\UpdateRiderStatusAction;
use App\DTOs\Api\V1\Rider\AppStateDTO;
use App\DTOs\Api\V1\Rider\Location\UpdateLocationDTO;
use App\DTOs\Api\V1\Rider\Profile\UpdateProfileDTO;
use App\DTOs\Api\V1\Rider\UpdateRiderStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Location\UpdateLocationRequest;
use App\Http\Requests\Api\V1\Rider\Profile\UpdateProfileImageRequest;
use App\Http\Requests\Api\V1\Rider\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Rider\UpdateRiderStatusRequest;
use App\Http\Resources\Api\V1\Rider\AppStateResource;
use App\Http\Resources\Api\V1\Rider\RiderResource;
use App\Models\Rider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Rider
 */
class RiderController extends Controller
{
    /**
     * Get rider profile
     *
     * Returns the authenticated rider's profile information including:
     * - Personal details (full name, email)
     * - Phone number with country code
     * - Profile image URL
     * - Total completed rides count
     * - Account status
     *
     * @authenticated
     */
    public function profile(): RiderResource
    {
        /** @var Rider $rider */
        $rider = auth('rider')->user();

        return new RiderResource($rider);
    }

    /**
     * Update rider profile
     *
     * Updates the authenticated rider's profile information.
     * Phone number must be exactly 8 digits.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function updateProfile(
        UpdateProfileRequest $request,
        UpdateProfileDTO $dto,
        UpdateProfileAction $action,
    ): RiderResource {
        /** @var Rider $rider */
        $rider = auth('rider')->user();

        $dto->getDataFromRequest($request);

        return new RiderResource($action($rider, $dto));
    }

    /**
     * Update rider profile image
     *
     * Updates the authenticated rider's profile image.
     * Accepts JPEG, PNG, and WebP formats with maximum size of 2MB.
     *
     * @authenticated
     *
     * @requestMediaType multipart/form-data
     *
     * @throws \Throwable
     */
    public function updateProfileImage(
        UpdateProfileImageRequest $request,
        UpdateProfileImageAction $action,
    ): RiderResource {
        /** @var Rider $rider */
        $rider = auth('rider')->user();

        return new RiderResource($action($rider, $request->file('image')));
    }

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

    /**
     * Update rider status
     *
     * Allows rider to change their status between ONLINE and OFFLINE.
     * Cannot change status if currently BUSY or has an active trip.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function updateStatus(
        UpdateRiderStatusRequest $request,
        UpdateRiderStatusDTO $dto,
        UpdateRiderStatusAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }
}
