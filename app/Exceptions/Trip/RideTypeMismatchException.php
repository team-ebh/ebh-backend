<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class RideTypeMismatchException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.ride_type_mismatch');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
