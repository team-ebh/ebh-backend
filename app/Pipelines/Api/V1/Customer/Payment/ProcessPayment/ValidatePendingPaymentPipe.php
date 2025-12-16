<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\ProcessPayment;

use App\Exceptions\Payment\PaymentAlreadyProcessedException;
use App\Exceptions\Payment\PaymentNotFoundException;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use Closure;

/**
 * Validate Pending Payment Pipe
 *
 * Validates that a pending payment exists for the given track ID
 */
readonly class ValidatePendingPaymentPipe
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @throws PaymentNotFoundException
     * @throws PaymentAlreadyProcessedException|\Throwable
     */
    public function handle(PaymentProcessContext $context, Closure $next): mixed
    {
        $payment = $this->paymentRepository->findByGatewayReferenceId($context->dto->trackId);

        throw_if(
            is_null($payment),
            PaymentNotFoundException::class
        );

        throw_if(
            ! $payment->canBeProcessed(),
            PaymentAlreadyProcessedException::class
        );

        $context->payment = $payment;

        return $next($context);
    }
}
