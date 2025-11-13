<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class RiderLocationNotAvailableException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.rider_location_not_available');
    }

    public function statusCode(): int
    {
        return 403;
    }
}
