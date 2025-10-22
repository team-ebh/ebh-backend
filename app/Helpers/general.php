<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use App\Services\SafeProcess;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('adminPanelDataFormat')) {
    function adminPanelDataFormat(): string
    {
        return 'M d, Y';
    }
}

if (! function_exists('adminPanelTimeFormat')) {
    function adminPanelTimeFormat(): string
    {
        return 'H:i';
    }
}

if (! function_exists('getDefaultImageUrl')) {
    function getDefaultImageUrl(): ?string
    {
        if (ApplicationEnvironmentEnum::isDevelopmentEnvironment()) {
            return asset('images/logo/light.png');
        }

        return null;
    }
}

if (! function_exists('getDefaultSVGUrl')) {
    function getDefaultSVGUrl(): ?string
    {
        if (ApplicationEnvironmentEnum::isDevelopmentEnvironment()) {
            return asset('images/logo/light.svg');
        }

        return null;
    }
}

if (! function_exists('defaultPrefixPhoneNumber')) {
    function defaultPrefixPhoneNumber(): string
    {
        return '+965';
    }
}

if (! function_exists('translated')) {
    function translated(Model $model, string $field): mixed
    {
        return $model->translated($field);
    }
}

if (! function_exists('safeProcess')) {

    function safeProcess(): SafeProcess
    {
        return new SafeProcess();
    }
}

if (! function_exists('generateOtpCode')) {

    /**
     * @throws \Random\RandomException
     */
    function generateOtpCode(): string
    {
        return str_pad((string) random_int(1111, 9999), 4, '0', STR_PAD_LEFT);
    }
}
