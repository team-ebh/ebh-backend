<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

class PaymentNotPaidException extends BaseException
{
    public function message(): string
    {
        return trans('payments.errors.payment_not_paid');
    }

    public function statusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
