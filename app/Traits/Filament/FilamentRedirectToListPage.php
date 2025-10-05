<?php

declare(strict_types=1);

namespace App\Traits\Filament;

trait FilamentRedirectToListPage
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
