<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class TripCannotBeCancelledByRiderException extends BaseException
{
    public function message(): string
    {
        return trans('trips.cannot_cancel_trip_status');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
