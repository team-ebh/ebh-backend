<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerHasPendingPaymentException extends BaseException
{
    public function message(): string
    {
        return trans('customers.admin.exceptions.has_pending_payment');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
