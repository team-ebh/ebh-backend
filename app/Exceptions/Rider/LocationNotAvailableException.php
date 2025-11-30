<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Response;

class LocationNotAvailableException extends BaseException
{
    public function message(): string
    {
        return trans('trips.location_not_available');
    }

    public function statusCode(): int
    {
        return Response::HTTP_NOT_ACCEPTABLE;
    }
}
