<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class CustomerAlreadyHasActiveTripException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.customer_already_has_active_trip');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
