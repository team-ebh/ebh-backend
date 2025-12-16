<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class TripPaymentNotAllowedException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.trip_payment_not_allowed');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
