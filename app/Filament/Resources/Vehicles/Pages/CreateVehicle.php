<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Pages;

use App\Filament\Resources\Vehicles\VehicleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove accessibility_feature_ids from data as it will be handled separately
        unset($data['accessibility_feature_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $accessibilityFeatureIds = $this->form->getRawState()['accessibility_feature_ids'] ?? [];

        if (! empty($accessibilityFeatureIds)) {
            $this->record->syncAccessibilityFeatures($accessibilityFeatureIds);
        }
    }
}
