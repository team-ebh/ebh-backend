<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class ScheduledTripCannotChangeRideTypeException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.scheduled_trip_cannot_change_ride_type');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
