<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Payment;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Process Payment DTO
 *
 * Base DTO for payment callback/webhook processing
 */
class ProcessPaymentDTO implements RequestDataTransferObject
{
    public string $trackId;

    public bool $isCallback;

    public ?Payment $payment = null;

    public ?string $paymentStatus = null;

    public ?string $deeplink = null;

    public function getDataFromRequest(Request $request): void
    {
        $this->trackId = $request->input('track_id');
        $this->isCallback = $request->routeIs('*.callback');
    }

    public function isWebhook(): bool
    {
        return ! $this->isCallback;
    }
}
