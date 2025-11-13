<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove accessibility_feature_ids from data as it will be handled separately
        unset($data['accessibility_feature_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        $accessibilityFeatureIds = $this->form->getRawState()['accessibility_feature_ids'] ?? [];

        $this->record->syncAccessibilityFeatures($accessibilityFeatureIds);
    }
}
