<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class RiderHasActiveTripException extends BaseException
{
    public function message(): string
    {
        return trans('riders.admin.exceptions.has_active_trip');
    }

    public function statusCode(): int
    {
        return 422;
    }
}
