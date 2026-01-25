<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class CustomerHasScheduledTripException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.customer_has_scheduled_trip');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
