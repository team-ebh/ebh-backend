<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip\History;

use App\Enums\Trip\TripHistoryTypeEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class TripHistoryDTO implements RequestDataTransferObject
{
    public int $customerId;

    public TripHistoryTypeEnum $type;

    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
        $this->type = TripHistoryTypeEnum::from($request->validated('type'));
    }
}
