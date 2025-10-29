<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AccessibilityRequirementsEnum: int implements HasIcon, HasLabel
{
    case WHEELCHAIR_ACCESSIBLE = 1;
    case OXYGEN_SUPPORT = 2;
    case PORTABLE_RAMP = 3;

    public function getLabel(): ?string
    {
        return trans('trips.api.accessibility_requirements.' . $this->name);
    }

    public function getDescription(): ?string
    {
        return trans('trips.api.accessibility_requirements.' . $this->name . '_description');
    }

    public function getIcon(): string | BackedEnum | null
    {
        return match ($this) {
            self::WHEELCHAIR_ACCESSIBLE => asset('images/trip/accessibility/wheelchair 1.svg'),
            self::OXYGEN_SUPPORT => asset('images/trip/accessibility/oxygen 1.svg'),
            self::PORTABLE_RAMP => asset('images/trip/accessibility/ramp 1.svg'),
        };
    }

    /**
     * Get the price in KWD for this accessibility requirement
     * Returns null if the service is included for free
     */
    public function getPrice(): ?float
    {
        return match ($this) {
            self::WHEELCHAIR_ACCESSIBLE => 3.000,
            self::OXYGEN_SUPPORT => 2.500,
            self::PORTABLE_RAMP => null, // Included for free
        };
    }
}
