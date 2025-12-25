<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class RiderAccountDisabledException extends BaseException
{
    public function message(): string
    {
        return trans('riders.api.exceptions.account_disabled');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
