<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

/**
 * App State DTO
 *
 * Data transfer object for getting customer app current state
 */
readonly class AppStateDTO implements RequestDataTransferObject
{
    public int $customerId;

    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
    }
}
