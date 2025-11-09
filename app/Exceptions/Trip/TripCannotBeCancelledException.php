<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class TripCannotBeCancelledException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.trip_cannot_be_cancelled');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
