<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class TripNotBelongToRiderException extends BaseException
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
