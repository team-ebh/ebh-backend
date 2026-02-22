<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingPageResource\Pages;

use App\Filament\Resources\OnboardingPageResource;
use App\Models\OnboardingPage;
use App\Traits\Filament\HasActivityLogHeaderActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOnboardingPage extends EditRecord
{
    use HasActivityLogHeaderActions;

    protected static string $resource = OnboardingPageResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                DeleteAction::make()
                    ->visible(fn (): bool => ! $this->getRecord()->{OnboardingPage::COLUMN_ENABLED}),
            ],
            $this->getActivityLogHeaderActions(),
        );
    }

    public function afterSave(): void
    {
        $this->getRecord()->makeOnlyOneEnabledOnboardingPage();
    }
}
