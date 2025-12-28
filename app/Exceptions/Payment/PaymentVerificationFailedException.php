<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use App\Exceptions\BaseException;

class PaymentVerificationFailedException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_verification_failed');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
