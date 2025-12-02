<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HandlesRiderDocuments;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateRider extends CreateRecord
{
    use FilamentRedirectToListPage;
    use HandlesRiderDocuments;
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = RiderResource::class;

    public function hasDatabaseTransactions(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store documents data for afterCreate (same as mutateFormDataBeforeSave in Edit)
        $this->documentsData = $data['documents'] ?? [];

        // Store accessibility certification IDs for afterCreate
        $this->accessibilityCertificationIds = $data['accessibility_certification_ids'] ?? [];

        // Remove documents and certifications from main data
        unset($data['documents'], $data['accessibility_certification_ids']);

        return $data;
    }

    public array $accessibilityFeatureIds = [];

    public array $accessibilityCertificationIds = [];

    protected function afterCreate(): void
    {
        $rider = $this->record;

        // Sync rider accessibility certifications
        $this->syncAccessibilityCertifications($rider);

        // Sync vehicle accessibility features
        if ($rider->vehicle) {
            $rider->vehicle->syncAccessibilityFeatures($this->accessibilityFeatureIds);
        }

        // Handle documents in CREATE mode - directly add files to RiderDocument
        $this->handleDocumentsForCreate($rider);
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

    protected function handleDocumentsForCreate(\App\Models\Rider $rider): void
    {
        $enabledDocuments = \App\Models\Document::query()
            ->where(\App\Models\Document::COLUMN_ENABLED, true)
            ->get();

        foreach ($enabledDocuments as $document) {
            $documentData = $this->documentsData[$document->id] ?? [];

            // Check if file was uploaded
            if (! empty($documentData['file'])) {
                // Create RiderDocument first
                $riderDocument = \App\Models\RiderDocument::create([
                    \App\Models\RiderDocument::COLUMN_RIDER_ID => $rider->id,
                    \App\Models\RiderDocument::COLUMN_DOCUMENT_ID => $document->id,
                ]);

                // Add file directly to RiderDocument (not from Rider!)
                if (! empty($documentData['file'])) {
                    $filePath = $documentData['file'];

                    // FileUpload stores the relative path, we need the full path
                    if (is_string($filePath)) {
                        $fullPath = storage_path('app/public/' . $filePath);

                        if (file_exists($fullPath)) {
                            // Add file directly to RiderDocument
                            $riderDocument
                                ->addMedia($fullPath)
                                ->toMediaCollection(\App\Models\RiderDocument::MEDIA_COLLECTION_NAME);

                            // Delete temporary file
                            @unlink($fullPath);
                        }
                    }
                }
            }
        }
    }
}
