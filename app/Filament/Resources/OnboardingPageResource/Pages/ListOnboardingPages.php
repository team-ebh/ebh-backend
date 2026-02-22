<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingPageResource\Pages;

use App\Filament\Resources\OnboardingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOnboardingPages extends ListRecords
{
    protected static string $resource = OnboardingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
