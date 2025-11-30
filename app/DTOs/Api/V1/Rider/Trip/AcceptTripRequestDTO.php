<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Http\Request;

/**
 * Accept Trip Request DTO
 *
 * Data transfer object for rider accepting a trip request
 */
class AcceptTripRequestDTO implements RequestDataTransferObject
{
    public int $riderId;

    public TripRequest $tripRequest;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth('rider')->id();
        $this->tripRequest = $request->route('tripRequest');
    }
}
