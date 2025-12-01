<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Response;

class TripNotInProgressException extends BaseException
{
    public function message(): string
    {
        return trans('trips.trip_not_in_progress');
    }

    public function statusCode(): int
    {
        return Response::HTTP_NOT_ACCEPTABLE;
    }
}
