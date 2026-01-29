<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetTripReceiptLinkDTO implements RequestDataTransferObject
{
    public int $customerId;

    public Trip $trip;

    public function getDataFromRequest(Request $request, ?Trip $trip = null): void
    {
        $this->customerId = Auth::guard('customer')->id();
        $this->trip = $trip ?? $request->route('trip');
    }
}
