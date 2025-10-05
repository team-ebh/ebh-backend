<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialException extends BaseException
{
    public function message(): string
    {
        return trans('auth.failed');
    }

    public function statusCode(): int
    {
        return Response::HTTP_NOT_ACCEPTABLE;
    }
}
