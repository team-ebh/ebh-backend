<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerAccountDisabledException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.account_disabled');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
