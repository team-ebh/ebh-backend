<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationEnvironmentEnum: string
{
    case LOCAL = 'local';
    case TESTING = 'testing';
    case STRESS = 'stress';
    case DEVELOPMENT_SERVER = 'dev';
    case TEST_SERVER = 'test';
    case STAGE_SERVER = 'stage';
    case PRODUCTION_SERVER = 'production';

    public static function getDevelopmentEnvironments(): array
    {
        return [
            self::LOCAL->value,
            self::TESTING->value,
        ];
    }

    public static function getLocalEnvironments(): array
    {
        return [
            self::LOCAL->value,
            self::TESTING->value,
        ];
    }

    public static function isLocalEnvironments(): bool
    {
        return in_array(config('app.env'), self::getLocalEnvironments());
    }

    public static function getRiskyEnvironments(): array
    {
        return [
            self::STAGE_SERVER->value,
            self::PRODUCTION_SERVER->value,
        ];
    }

    public static function isRiskyEnvironment(): bool
    {
        return in_array(config('app.env'), self::getRiskyEnvironments());
    }

    public static function isDevelopmentEnvironment(): bool
    {
        return in_array(config('app.env'), self::getDevelopmentEnvironments());
    }

    public static function isStage(): bool
    {
        return config('app.env') === self::STAGE_SERVER->value;
    }

    public static function isProduction(): bool
    {
        return config('app.env') === self::PRODUCTION_SERVER->value;
    }
}
