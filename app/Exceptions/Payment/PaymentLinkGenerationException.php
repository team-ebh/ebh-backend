<?php

declare(strict_types=1);

namespace App\Exceptions\Payment;

use App\Exceptions\BaseException;

class PaymentLinkGenerationException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_link_generation_failed');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
