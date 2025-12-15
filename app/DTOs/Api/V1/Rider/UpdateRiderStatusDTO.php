<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider;

use App\Enums\Rider\RiderStatusEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class UpdateRiderStatusDTO implements RequestDataTransferObject
{
    public int $riderId;

    public RiderStatusEnum $status;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth('rider')->id();
        $this->status = RiderStatusEnum::from($request->input('status'));
    }
}
