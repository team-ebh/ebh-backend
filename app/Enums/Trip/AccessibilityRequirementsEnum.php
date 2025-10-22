<?php

declare(strict_types=1);

namespace App\Enums\Trip;

use Filament\Support\Contracts\HasLabel;

enum AccessibilityRequirementsEnum: int implements HasLabel
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
}
