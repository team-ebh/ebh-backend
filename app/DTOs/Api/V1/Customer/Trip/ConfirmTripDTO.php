<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Trip;

use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Http\Request;

/**
 * Confirm Trip DTO
 *
 * Data Transfer Object for trip confirmation with payment method and ride type data
 */
class ConfirmTripDTO extends ChangeRideTypeDTO
{
    public PaymentMethodEnum $paymentMethod;

    /**
     * Populate DTO from request data
     */
    public function getDataFromRequest(Request $request): void
    {
        parent::getDataFromRequest($request);

        $this->paymentMethod = $request->enum('payment_method', PaymentMethodEnum::class);
    }
}
