<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class PaymentNotFoundException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_not_found');
    }

    public function statusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
