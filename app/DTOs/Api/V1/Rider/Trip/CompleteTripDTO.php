<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\TripRequest;
use Illuminate\Http\Request;

/**
 * Complete Trip Location DTO
 *
 * Data transfer object for rider completing a trip location
 */
class CompleteTripDTO implements RequestDataTransferObject
{
    public int $riderId;

    public TripRequest $tripRequest;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth('rider')->id();
        $this->tripRequest = $request->route('tripRequest');
    }
}
