<?php

declare(strict_types=1);

namespace App\Enums\Rider;

use Filament\Support\Contracts\HasLabel;

enum AccessibilityCertificationEnum: string implements HasLabel
{
    case WHEELCHAIR_ACCESSIBLE = 'wheelchair_accessible';
    case ARABIC_SIGN_LANGUAGE = 'arabic_sign_language';
    case VISUAL_ASSISTANCE = 'visual_assistance';
    case HEARING_ASSISTANCE = 'hearing_assistance';

    public function getLabel(): ?string
    {
        return trans('riders.admin.fields.accessibility_certifications.' . $this->value);
    }

    /**
     * Get all available certification options as array
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
