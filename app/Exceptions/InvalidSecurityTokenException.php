<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidSecurityTokenException extends BaseException
{
    public function message(): string
    {
        return trans('auth.exceptions.invalid_security_token');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
