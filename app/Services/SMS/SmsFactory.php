<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Enums\SMS\SmsProvidersEnum;
use App\Services\SMS\Providers\KWTSmsProvider;
use App\Services\SMS\Providers\RouteMobileProvider;

class SmsFactory
{
    public static function build(): SmsInterface
    {
        $activeProvider = SmsProvidersEnum::tryFrom(config('sms.active_provider'));

        return match ($activeProvider->value) {
            SmsProvidersEnum::ROUTE_MOBILE->value => new RouteMobileProvider(),
            SmsProvidersEnum::KWT_SMS->value => new KWTSmsProvider(),
        };
    }
}
