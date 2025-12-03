<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use Illuminate\Http\Request;

class GetEstimatedArrivalTimeDTO implements RequestDataTransferObject
{
    public int $customerId;

    public Trip $trip;

    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth()->guard('customer')->id();
        $this->trip = $request->route('trip');
    }
}
