<?php

declare(strict_types=1);

namespace App\Enums\Customer;

enum VerificationCodeTypeEnum: int
{
    case SIGN_UP = 1;
    case SIGN_IN = 2;
}
