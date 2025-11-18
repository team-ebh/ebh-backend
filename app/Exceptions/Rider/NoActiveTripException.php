<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;
use Symfony\Component\HttpFoundation\Response;

class NoActiveTripException extends BaseException
{
    public function message(): string
    {
        return trans('trips.no_active_trip');
    }

    public function statusCode(): int
    {
        return Response::HTTP_NO_CONTENT;
    }
}
