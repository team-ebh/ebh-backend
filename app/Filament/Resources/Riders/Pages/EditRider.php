<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Actions\Filament\Rider\ValidateRiderStatusChangeAction;
use App\Exceptions\Rider\RiderHasActiveTripException;
use App\Filament\Resources\Riders\RiderResource;
use App\Models\Rider;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HandlesRiderDocuments;
use Filament\Actions;
use Filament\Notifications\Notification;
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

    protected function beforeSave(): void
    {
        $validateAction = app(ValidateRiderStatusChangeAction::class);

        $currentEnabled = $this->record->getOriginal(Rider::COLUMN_ENABLED);
        $newEnabled = $this->data['enabled'] ?? null;

        // Check if enabled status is being changed
        if ($currentEnabled !== $newEnabled && $newEnabled !== null) {
            // Convert to boolean if needed
            $currentEnabledBool = is_bool($currentEnabled) ? $currentEnabled : (bool) $currentEnabled;
            $newEnabledBool = is_bool($newEnabled) ? $newEnabled : (bool) $newEnabled;

            try {
                $validateAction(
                    $this->record->{Rider::COLUMN_ID},
                    $currentEnabledBool,
                    $newEnabledBool
                );
            } catch (RiderHasActiveTripException $e) {
                Notification::make()
                    ->danger()
                    ->title(trans('riders.admin.exceptions.has_active_trip'))
                    ->send();

                $this->halt();
            }
        }
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

        // Store accessibility certification IDs for afterSave
        $this->accessibilityCertificationIds = $data['accessibility_certification_ids'] ?? [];

        // Remove documents and certifications from main data
        unset($data['documents'], $data['accessibility_certification_ids']);

        return $data;
    }

    public array $accessibilityFeatureIds = [];

    public array $accessibilityCertificationIds = [];

    protected function afterSave(): void
    {
        $rider = $this->record;

        // Sync rider accessibility certifications
        $this->syncAccessibilityCertifications($rider);

        // Sync vehicle accessibility features
        if ($rider->vehicle) {
            $rider->vehicle->syncAccessibilityFeatures($this->accessibilityFeatureIds);
        }

        // Handle documents - create/update RiderDocument records and move media
        $this->handleDocuments($rider);
    }

    protected function syncAccessibilityCertifications(\App\Models\Rider $rider): void
    {
        // Delete existing certifications
        $rider->accessibilityCertifications()->delete();

        // Create new certifications
        foreach ($this->accessibilityCertificationIds as $certificationId) {
            \App\Models\RiderAccessibilityCertification::create([
                \App\Models\RiderAccessibilityCertification::COLUMN_RIDER_ID => $rider->id,
                \App\Models\RiderAccessibilityCertification::COLUMN_CERTIFICATION_TYPE => $certificationId,
            ]);
        }
    }

    protected function beforeFill(): void
    {
        // Ensure documents, vehicle, and certifications relations are loaded
        $this->record->load([
            'documents.media',
            'vehicle.accessibilityFeatures',
            'accessibilityCertifications',
        ]);
    }
}
