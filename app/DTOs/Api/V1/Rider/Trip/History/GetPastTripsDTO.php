<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Trip\History;

use App\Enums\Trip\TripHistoryFilterEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetPastTripsDTO implements RequestDataTransferObject
{
    public int $riderId;

    public TripHistoryFilterEnum $filter;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = Auth::guard('rider')->id();
        $filterValue = $request->input('filter');
        $this->filter = $filterValue ? TripHistoryFilterEnum::tryFrom($filterValue) ?? TripHistoryFilterEnum::ALL : TripHistoryFilterEnum::ALL;
    }
}
