<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerBeforeRegisteredException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.before_registered');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
