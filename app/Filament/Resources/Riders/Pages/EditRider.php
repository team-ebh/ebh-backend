<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HandlesRiderDocuments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRider extends EditRecord
{
    use FilamentRedirectToListPage;
    use HandlesRiderDocuments;

    protected static string $resource = RiderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->url(fn (): string => RiderResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function hasDatabaseTransactions(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Prepare documents for form display
        $this->prepareDocumentsForForm($this->record, $data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store documents data for afterSave
        $this->documentsData = $data['documents'] ?? [];

        // Remove documents from main data
        unset($data['documents']);

        return $data;
    }

    public array $accessibilityFeatureIds = [];

    protected function afterSave(): void
    {
        $rider = $this->record;

        // Sync vehicle accessibility features
        if ($rider->vehicle) {
            $rider->vehicle->syncAccessibilityFeatures($this->accessibilityFeatureIds);
        }

        // Handle documents - create/update RiderDocument records and move media
        $this->handleDocuments($rider);
    }

    protected function beforeFill(): void
    {
        // Ensure documents and vehicle relations are loaded
        $this->record->load([
            'documents.media',
            'vehicle.accessibilityFeatures',
        ]);
    }
}
