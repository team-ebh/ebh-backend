<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use App\Exceptions\BaseException;

class PaymentNotFoundException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_not_found');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
