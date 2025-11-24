<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Response;

class InvalidTripActionException extends BaseException
{
    public function message(): string
    {
        return trans('trips.invalid_trip_action');
    }

    public function statusCode(): int
    {
        return Response::HTTP_NOT_ACCEPTABLE;
    }
}
