<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use App\Models\Document;
use App\Models\RiderDocument;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateRider extends CreateRecord
{
    use FilamentRedirectToListPage;
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = RiderResource::class;

    public function hasDatabaseTransactions(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store documents data for afterCreate
        $this->documentsData = $data['documents'] ?? [];

        // Remove documents from main data
        unset($data['documents']);

        return $data;
    }

    protected array $documentsData = [];

    public array $accessibilityFeatureIds = [];

    protected function afterCreate(): void
    {
        $rider = $this->record;

        // Sync vehicle accessibility features
        if ($rider->vehicle) {
            $rider->vehicle->syncAccessibilityFeatures($this->accessibilityFeatureIds);
        }

        // Get all enabled documents
        $enabledDocuments = Document::query()
            ->where(Document::COLUMN_ENABLED, true)
            ->get();

        foreach ($enabledDocuments as $document) {
            $documentData = $this->documentsData[$document->id] ?? [];

            // Check if there's any data for this document
            if (! empty($documentData['file']) || isset($documentData['expires_at'])) {
                // Create RiderDocument
                $riderDocument = RiderDocument::query()->create([
                    RiderDocument::COLUMN_RIDER_ID => $rider->id,
                    RiderDocument::COLUMN_DOCUMENT_ID => $document->id,
                    RiderDocument::COLUMN_EXPIRES_AT => $documentData['expires_at'] ?? null,
                ]);

                // Handle media from temporary collection on rider
                $temporaryMedia = $rider->getMedia("document_{$document->id}");

                if ($temporaryMedia->isNotEmpty()) {
                    foreach ($temporaryMedia as $media) {
                        $media->move($riderDocument, 'rider_documents');
                    }
                }

                // Clean up temporary collection
                $rider->clearMediaCollection("document_{$document->id}");
            }
        }
    }
}
