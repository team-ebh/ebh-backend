<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\BaseException;

class CustomerAccountDeletedException extends BaseException
{
    public function message(): string
    {
        return trans('customers.api.exceptions.account_deleted');
    }

    public function statusCode(): int
    {
        return 406;
    }
}
