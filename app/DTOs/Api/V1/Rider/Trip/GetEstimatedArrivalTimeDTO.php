<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\TripRequest;
use Illuminate\Http\Request;

/**
 * Get Estimated Arrival Time DTO
 *
 * Data transfer object for getting estimated arrival time
 */
readonly class GetEstimatedArrivalTimeDTO implements RequestDataTransferObject
{
    public int $riderId;

    public TripRequest $tripRequest;

    /**
     * Create DTO from request
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth()->guard('rider')->id();
        $this->tripRequest = $request->route('tripRequest');
    }
}
