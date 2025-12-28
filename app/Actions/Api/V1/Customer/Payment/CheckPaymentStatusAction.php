<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\DTOs\Api\V1\Customer\Payment\CheckPaymentStatusDTO;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Payment;
use App\Models\Trip;

class CheckPaymentStatusAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(CheckPaymentStatusDTO $dto): Payment
    {
        $payment = $dto->payment;

        throw_if(
            ! $payment->isForCustomer($dto->customerId),
            PaymentNotFoundException::class,
        );

        // Load trip with payment_method
        $payment->load([
            'trip:' . Trip::COLUMN_ID . ',' . Trip::COLUMN_PAYMENT_METHOD,
        ]);

        return $payment;
    }
}
