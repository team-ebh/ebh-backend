<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerMustBeRegisterException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.must_be_registered');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
