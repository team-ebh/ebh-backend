<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\DTOs\Api\V1\Customer\Payment\GetReceiptLinkDTO;
use App\Exceptions\PaymentNotFoundException;
use App\Exceptions\PaymentNotPaidException;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;

class GetReceiptLinkAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(GetReceiptLinkDTO $dto): array
    {
        return safeProcess()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($dto) {
                $payment = $dto->payment;

                throw_if(
                    ! $payment->isForCustomer($dto->customerId),
                    PaymentNotFoundException::class
                );

                throw_if(
                    ! $payment->isPaid(),
                    PaymentNotPaidException::class
                );

                // Generate temporary signed URL (valid for 10 minutes)
                $link = URL::temporarySignedRoute(
                    'v1.customers.payments.download-receipt',
                    now()->addMinutes(10),
                    ['payment' => $payment->{Payment::COLUMN_PAYMENT_NUMBER}]
                );

                return compact('link');
            });
    }
}
