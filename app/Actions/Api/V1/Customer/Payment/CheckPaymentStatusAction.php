<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\DTOs\Api\V1\Customer\Payment\CheckPaymentStatusDTO;
use App\Exceptions\PaymentNotFoundException;
use App\Models\Order;
use App\Models\Payment;

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

        // Load order with payment_method and trips
        $payment->load([
            'order:' . Order::COLUMN_ID . ',' . Order::COLUMN_PAYMENT_METHOD,
            'order.trips:id,order_id',
        ]);

        return $payment;
    }
}
