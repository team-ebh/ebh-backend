<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

class CannotChangeRiderStatusException extends BaseException
{
    public function message(): string
    {
        return trans('riders.api.exceptions.cannot_change_status');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
