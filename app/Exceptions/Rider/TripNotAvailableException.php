<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class TripNotAvailableException extends BaseException
{
    public function message(): string
    {
        return trans('riders.api.errors.trip_not_available');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
