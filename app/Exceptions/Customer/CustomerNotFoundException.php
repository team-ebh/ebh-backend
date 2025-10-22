<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerNotFoundException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.not_found');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
