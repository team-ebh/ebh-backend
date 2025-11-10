<?php

declare(strict_types=1);

namespace App\Exceptions\Trip;

use App\Exceptions\BaseException;

class TripNotDraftException extends BaseException
{
    public function message(): string
    {
        return trans('trips.api.exceptions.trip_not_draft');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
