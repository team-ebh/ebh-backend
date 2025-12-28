<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerHasActiveTripException extends BaseException
{
    public function message(): string
    {
        return trans('customers.admin.exceptions.has_active_trip');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
