<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetTripReceiptLinkDTO implements RequestDataTransferObject
{
    public int $riderId;

    public Trip $trip;

    public function getDataFromRequest(Request $request, ?Trip $trip = null): void
    {
        $this->riderId = Auth::guard('rider')->id();
        $this->trip = $trip ?? $request->route('trip');
    }
}
