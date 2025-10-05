<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidRequestException extends BaseException
{
    public function message(): string
    {
        return trans('auth.invalid_request');
    }

    public function statusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
