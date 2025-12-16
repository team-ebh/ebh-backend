<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use App\Exceptions\BaseException;

class PaymentAlreadyProcessedException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_already_processed');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
