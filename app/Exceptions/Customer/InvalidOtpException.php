<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class InvalidOtpException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.invalid_otp');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
