<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Models\Payment;
use App\Models\Trip;

/**
 * Payment Link Context
 *
 * Context object passed through the payment link generation pipeline
 */
class PaymentLinkContext
{
    public ?string $paymentLink = null;

    public ?string $gatewayReferenceId = null;

    public ?Trip $trip;

    public ?Payment $payment = null;

    public function __construct() {}
}
