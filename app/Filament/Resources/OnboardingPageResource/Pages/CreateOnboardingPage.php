<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingPageResource\Pages;

use App\Filament\Resources\OnboardingPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOnboardingPage extends CreateRecord
{
    protected static string $resource = OnboardingPageResource::class;

    public function afterCreate(): void
    {
        $this->getRecord()->makeOnlyOneEnabledOnboardingPage();
    }
}
