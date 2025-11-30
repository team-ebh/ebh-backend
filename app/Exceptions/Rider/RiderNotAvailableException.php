<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class RiderNotAvailableException extends BaseException
{
    public function message(): string
    {
        return trans('riders.api.errors.rider_not_available');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
