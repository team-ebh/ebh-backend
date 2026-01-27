<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Profile\UpdateProfileAction;
use App\Actions\Api\V1\Rider\Profile\UpdateProfileImageAction;
use App\DTOs\Api\V1\Rider\Profile\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Profile\UpdateProfileImageRequest;
use App\Http\Requests\Api\V1\Rider\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Rider\RiderResource;
use App\Models\Rider;

/**
 * @tags Rider Profile
 */
class ProfileController extends Controller
{
    /**
     * Get rider profile
     *
     * Returns the authenticated rider's profile information including:
     * - Personal details (full name, email)
     * - Phone number with country code
     * - Profile image URL
     * - Total completed rides count
     * - Vehicle information
     * - Account status
     *
     * @authenticated
     */
    public function show(): RiderResource
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
    public function update(
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
    public function store(
        UpdateProfileImageRequest $request,
        UpdateProfileImageAction $action,
    ): RiderResource {
        /** @var Rider $rider */
        $rider = auth('rider')->user();

        return new RiderResource($action($rider, $request->file('image')));
    }
}
