<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Trip;
use Illuminate\Http\Request;

/**
 * Confirm Trip DTO
 *
 * Data Transfer Object for trip confirmation
 */
class ConfirmTripDTO implements RequestDataTransferObject
{
    public ?int $customerId;

    public Trip $trip;

    public PaymentMethodEnum $paymentMethod;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        $this->customerId = auth('customer')->id();
        $this->trip = $request->route()->parameter('trip');
        $this->paymentMethod = $request->enum('payment_method', PaymentMethodEnum::class);
    }
}
