<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use App\Models\Document;
use App\Models\RiderDocument;
use App\Traits\Filament\FilamentRedirectToListPage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRider extends EditRecord
{
    use FilamentRedirectToListPage;

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
        $rider = $this->record;

        // Load existing rider documents
        $riderDocuments = $rider->documents()
            ->with(['document', 'media'])
            ->get()
            ->keyBy('document_id');

        // Get all enabled documents
        $enabledDocuments = Document::query()
            ->where(Document::COLUMN_ENABLED, true)
            ->get();

        // Prepare document data for form
        $data['documents'] = [];

        foreach ($enabledDocuments as $document) {
            $riderDocument = $riderDocuments->get($document->id);

            if ($riderDocument) {
                // Copy media from RiderDocument to Rider temporarily for display
                $media = $riderDocument->getMedia('rider_documents');

                if ($media->isNotEmpty()) {
                    foreach ($media as $mediaItem) {
                        try {
                            $rider->addMediaFromDisk($mediaItem->getPath(), $mediaItem->disk)
                                ->usingName($mediaItem->name)
                                ->usingFileName($mediaItem->file_name)
                                ->toMediaCollection("document_{$document->id}");
                        } catch (\Exception $e) {
                            if (file_exists($mediaItem->getPath())) {
                                $rider->addMedia($mediaItem->getPath())
                                    ->usingName($mediaItem->name)
                                    ->usingFileName($mediaItem->file_name)
                                    ->toMediaCollection("document_{$document->id}");
                            }
                        }
                    }
                }

                $data['documents'][$document->id] = [
                    'expires_at' => $riderDocument->{RiderDocument::COLUMN_EXPIRES_AT},
                ];
            }
        }

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

    protected array $documentsData = [];

    public array $accessibilityFeatureIds = [];

    protected function afterSave(): void
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

            // Get or create RiderDocument
            $riderDocument = RiderDocument::firstOrCreate(
                [
                    RiderDocument::COLUMN_RIDER_ID => $rider->id,
                    RiderDocument::COLUMN_DOCUMENT_ID => $document->id,
                ]
            );

            // Update expires_at if provided
            if (isset($documentData['expires_at'])) {
                $riderDocument->{RiderDocument::COLUMN_EXPIRES_AT} = $documentData['expires_at'];
                $riderDocument->save();
            }

            // Handle media from temporary collection
            $temporaryMedia = $rider->getMedia("document_{$document->id}");
            $existingMedia = $riderDocument->getMedia('rider_documents');

            if ($temporaryMedia->isNotEmpty()) {
                // Check if there are new uploads
                $temporaryUuids = $temporaryMedia->pluck('uuid')->toArray();
                $existingUuids = $existingMedia->pluck('uuid')->toArray();

                if (array_diff($temporaryUuids, $existingUuids)) {
                    // Clear old media and move new media
                    $riderDocument->clearMediaCollection('rider_documents');

                    foreach ($temporaryMedia as $media) {
                        $media->move($riderDocument, 'rider_documents');
                    }
                }
            } elseif ($existingMedia->isNotEmpty() && empty($documentData['file'] ?? [])) {
                // User removed the file
                $riderDocument->clearMediaCollection('rider_documents');
            }

            // Clean up temporary collection
            $rider->clearMediaCollection("document_{$document->id}");
        }
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
