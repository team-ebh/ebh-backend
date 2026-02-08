<?php

declare(strict_types=1);

namespace App\Enums\SMS;

enum SmsProvidersEnum: string
{
    case ROUTE_MOBILE = 'route_mobile';
    case KWT_SMS = 'kwt_sms';
}
