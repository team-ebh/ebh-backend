<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Earnings;

use App\Enums\Rider\EarningsFilterEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetEarningsReportDTO implements RequestDataTransferObject
{
    public int $riderId;

    public EarningsFilterEnum $filter;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = Auth::guard('rider')->id();
        $filterValue = $request->input('filter');
        $this->filter = $filterValue
            ? EarningsFilterEnum::tryFrom($filterValue) ?? EarningsFilterEnum::TODAY
            : EarningsFilterEnum::TODAY;
    }
}
