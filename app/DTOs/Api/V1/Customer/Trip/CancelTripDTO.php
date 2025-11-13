<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use Illuminate\Http\Request;

/**
 * Cancel Trip DTO
 *
 * Data Transfer Object for trip cancellation
 */
class CancelTripDTO implements RequestDataTransferObject
{
    public ?int $customerId;

    public Trip $trip;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
        $this->trip = $request->route()->parameter('trip');
    }
}
