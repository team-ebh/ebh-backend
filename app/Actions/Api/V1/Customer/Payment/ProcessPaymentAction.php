<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\DTOs\Api\V1\Customer\Payment\ProcessPaymentDTO;
use App\Pipelines\Api\V1\Customer\Payment\ProcessPayment\CheckPaymentStatusPipe;
use App\Pipelines\Api\V1\Customer\Payment\ProcessPayment\GenerateDeeplinkPipe;
use App\Pipelines\Api\V1\Customer\Payment\ProcessPayment\PaymentProcessContext;
use App\Pipelines\Api\V1\Customer\Payment\ProcessPayment\UpdatePaymentStatusPipe;
use App\Pipelines\Api\V1\Customer\Payment\ProcessPayment\ValidatePendingPaymentPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Process Payment Action
 *
 * Processes payment callback/webhook with clean pipeline pattern
 * Flow: Validate → Check Status → Update → Generate Deeplink (callback only)
 */
readonly class ProcessPaymentAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(ProcessPaymentDTO $dto): PaymentProcessContext
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->process($dto));
    }

    /**
     * Main processing pipeline
     *
     * @throws \Throwable
     */
    private function process(ProcessPaymentDTO $dto): PaymentProcessContext
    {
        $context = new PaymentProcessContext($dto);

        /** @var PaymentProcessContext $result */
        $result = app(Pipeline::class)
            ->send($context)
            ->through([
                ValidatePendingPaymentPipe::class,
                CheckPaymentStatusPipe::class,
                UpdatePaymentStatusPipe::class,
                GenerateDeeplinkPipe::class,
            ])
            ->thenReturn();

        return $result;
    }
}
