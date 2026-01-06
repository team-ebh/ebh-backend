<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class ScheduledTripCannotBeConfirmedException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.scheduled_trip_cannot_be_confirmed');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
