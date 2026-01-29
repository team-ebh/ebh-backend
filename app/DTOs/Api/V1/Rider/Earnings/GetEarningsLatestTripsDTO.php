<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Earnings;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetEarningsLatestTripsDTO implements RequestDataTransferObject
{
    public int $riderId;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = Auth::guard('rider')->id();
    }
}
