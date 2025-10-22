<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Trip\GetTripFormDataAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Customer\Trip\TripFormDataResource;

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
}
