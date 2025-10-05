<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Illuminate\Support\Str;

trait TranslatableResourceLabels
{
    protected static function getResourceKey(): string
    {
        return Str::of(class_basename(static::class))
            ->beforeLast('Resource')
            ->kebab()
            ->plural()
            ->lower()
            ->value();
    }

    public static function getModelLabel(): string
    {
        return trans(static::getResourceKey() . '.admin.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans(static::getResourceKey() . '.admin.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return trans(static::getResourceKey() . '.admin.navigation_label');
    }
}
