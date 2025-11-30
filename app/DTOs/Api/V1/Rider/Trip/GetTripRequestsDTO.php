<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

/**
 * Get Trip Requests DTO
 *
 * Data transfer object for retrieving available trip requests for a rider
 */
class GetTripRequestsDTO implements RequestDataTransferObject
{
    public int $riderId;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth('rider')->id();
    }
}
