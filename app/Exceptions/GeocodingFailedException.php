<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class GeocodingFailedException extends BaseException
{
    public function message(): string
    {
        return trans('errors.geocoding.failed');
    }

    public function statusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
