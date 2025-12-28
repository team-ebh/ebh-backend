<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class TripNotBelongToCustomerException extends BaseException
{
    public function message(): string
    {
        return trans('trips.not_your_trip');
    }

    public function statusCode(): int
    {
        return 403;
    }
}
