<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\GetAppStateAction;
use App\Actions\Api\V1\Customer\Profile\UpdateProfileAction;
use App\Actions\Api\V1\Customer\Profile\UpdateProfileImageAction;
use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\DTOs\Api\V1\Customer\Profile\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Profile\UpdateProfileImageRequest;
use App\Http\Requests\Api\V1\Customer\Profile\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Customer\AppStateResource;
use App\Http\Resources\Api\V1\Customer\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

/**
 * @tags Customer
 */
class CustomerController extends Controller
{
    /**
     * Get customer profile
     *
     * Returns the authenticated customer's profile information including:
     * - Personal details (first name, last name, email)
     * - Phone number with country code
     * - Profile image URL
     * - Total completed rides count
     * - Account status
     *
     * @authenticated
     */
    public function profile(): CustomerResource
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return new CustomerResource($customer);
    }

    /**
     * Update customer profile
     *
     * Updates the authenticated customer's profile information.
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
    ): CustomerResource {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $dto->getDataFromRequest($request);

        return new CustomerResource($action($customer, $dto));
    }

    /**
     * Update customer profile image
     *
     * Updates the authenticated customer's profile image.
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
    ): CustomerResource {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return new CustomerResource($action($customer, $request->file('image')));
    }

    /**
     * Get current app state
     *
     * Returns the current state of the customer app to help mobile app determine its state
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
