<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class TripStatusCannotBeCheckedException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.trip_status_cannot_be_checked');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
