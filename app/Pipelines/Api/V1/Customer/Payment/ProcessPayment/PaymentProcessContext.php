<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\DTOs\Api\V1\Customer\Payment\ProcessPaymentDTO;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment;

/**
 * Payment Process Context
 *
 * Context object passed through the payment processing pipeline (callback/webhook)
 */
class PaymentProcessContext
{
    public ProcessPaymentDTO $dto;

    public ?Payment $payment = null;

    public ?PaymentStatusEnum $paymentStatus = null;

    public ?string $deeplink = null;

    public function __construct(ProcessPaymentDTO $dto)
    {
        $this->dto = $dto;
    }
}
