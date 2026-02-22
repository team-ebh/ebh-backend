<?php

declare(strict_types=1);

namespace App\Enums\Onboarding;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OnboardingApplicationTypeEnum: string implements HasColor, HasLabel
{
    case CUSTOMER = 'customer';
    case RIDER = 'rider';

    public function getLabel(): ?string
    {
        return trans('onboarding-pages.admin.application_types.' . $this->value);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::CUSTOMER => 'success',
            self::RIDER => 'info',
        };
    }
}
