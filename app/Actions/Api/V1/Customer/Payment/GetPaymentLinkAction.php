<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\Exceptions\Trip\TripNotFoundException;
use App\Exceptions\Trip\TripPaymentNotAllowedException;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\CheckExistingPendingPaymentPipe;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\CreatePaymentRecordPipe;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\GeneratePaymentLinkPipe;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\LoadLastTripPipe;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\PaymentLinkContext;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\UpdatePaymentLinkPipe;
use App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink\ValidatePaymentEligibilityPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Payment Link Action
 *
 * Generates payment link for completed trips with KNET payment method
 * Uses Pipeline pattern: Load Trip → Validate → Generate Link
 */
readonly class GetPaymentLinkAction
{
    /**
     * Execute the action
     *
     * @throws TripNotFoundException
     * @throws TripPaymentNotAllowedException
     * @throws \Throwable
     */
    public function __invoke(): string
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->process());
    }

    /**
     * Main processing pipeline
     *
     * @throws \Throwable
     */
    private function process(): string
    {
        $context = new PaymentLinkContext();

        /** @var PaymentLinkContext $result */
        $result = app(Pipeline::class)
            ->send($context)
            ->through([
                LoadLastTripPipe::class,
                ValidatePaymentEligibilityPipe::class,
                CheckExistingPendingPaymentPipe::class,
                CreatePaymentRecordPipe::class,
                GeneratePaymentLinkPipe::class,
                UpdatePaymentLinkPipe::class,
            ])
            ->thenReturn();

        return $result->paymentLink;
    }
}
