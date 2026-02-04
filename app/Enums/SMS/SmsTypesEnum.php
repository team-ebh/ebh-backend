<?php

declare(strict_types=1);

namespace App\Enums\SMS;

enum SmsTypesEnum: int
{
    case SIGNUP = 1;
    case SIGNIN = 2;
    case DELETE_ACCOUNT = 3;
}
