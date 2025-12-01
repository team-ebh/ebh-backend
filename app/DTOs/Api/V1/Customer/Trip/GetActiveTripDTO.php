<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

/**
 * Get Active Trip DTO
 *
 * Data transfer object for retrieving customer's active trip
 */
readonly class GetActiveTripDTO implements RequestDataTransferObject
{
    public int $customerId;

    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
    }
}
