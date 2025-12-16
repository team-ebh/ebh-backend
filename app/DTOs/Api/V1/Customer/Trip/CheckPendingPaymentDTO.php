<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class CheckPendingPaymentDTO implements RequestDataTransferObject
{
    public int $customerId;

    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = getAuthenticatedUser()->id;
    }
}
