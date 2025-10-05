<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidTokenException extends BaseException
{
    public function message(): string
    {
        return trans('auth.token');
    }

    public function statusCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }
}
