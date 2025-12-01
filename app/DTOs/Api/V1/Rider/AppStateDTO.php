<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

/**
 * App State DTO
 *
 * Data transfer object for getting rider app current state
 */
readonly class AppStateDTO implements RequestDataTransferObject
{
    public int $riderId;

    public function getDataFromRequest(Request $request): void
    {
        $this->riderId = auth('rider')->id();
    }
}
