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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract document fields and store for afterCreate
        $this->documentFiles = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'document_')) {
                $documentId = (int) str_replace('document_', '', $key);
                $this->documentFiles[$documentId] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }

    protected array $documentFiles = [];

    protected function afterCreate(): void
    {
        $rider = $this->record;

        foreach ($this->documentFiles as $documentId => $files) {
            if (empty($files)) {
                continue;
            }

            $document = Document::find($documentId);
            if (! $document) {
                continue;
            }

            // Get or create RiderDocument
            $riderDocument = RiderDocument::firstOrCreate(
                [
                    RiderDocument::COLUMN_RIDER_ID => $rider->id,
                    RiderDocument::COLUMN_DOCUMENT_ID => $documentId,
                ]
            );

            // Get media from rider's temporary collection and move to riderDocument
            $collectionName = "document_{$documentId}";
            $mediaItems = $rider->getMedia($collectionName);

            foreach ($mediaItems as $media) {
                // Move media from rider to riderDocument
                $media->move($riderDocument, 'documents');
            }

            // Clean up temporary collection
            $rider->clearMediaCollection($collectionName);
        }
    }
}
