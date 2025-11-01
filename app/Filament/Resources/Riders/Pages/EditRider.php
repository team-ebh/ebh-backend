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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rider = $this->record;

        // Load existing documents and populate form fields
        $riderDocuments = $rider->documents()->with('document')->get();

        foreach ($riderDocuments as $riderDocument) {
            $documentId = $riderDocument->document_id;
            $media = $riderDocument->firstMedia('documents');

            if ($media) {
                $collectionName = "document_{$documentId}";

                // Copy media from riderDocument to rider's temporary collection for form display
                try {
                    $rider->addMediaFromDisk($media->getPath(), $media->disk)
                        ->usingName($media->name)
                        ->usingFileName($media->file_name)
                        ->toMediaCollection($collectionName);
                } catch (\Exception $e) {
                    // If disk method fails, try direct path
                    try {
                        if (file_exists($media->getPath())) {
                            $rider->addMedia($media->getPath())
                                ->usingName($media->name)
                                ->usingFileName($media->file_name)
                                ->toMediaCollection($collectionName);
                        }
                    } catch (\Exception $e2) {
                        // If all fails, SpatieMediaLibraryFileUpload will handle via URL
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract document fields and store for afterSave
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

    protected function afterSave(): void
    {
        $rider = $this->record;

        foreach ($this->documentFiles as $documentId => $files) {
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

            $collectionName = "document_{$documentId}";
            $mediaItems = $rider->getMedia($collectionName);

            // Check if new files were uploaded (not existing URLs)
            $hasNewFiles = ! empty($files) && ! empty($mediaItems);

            if ($hasNewFiles) {
                // Clear old media from riderDocument
                $riderDocument->clearMediaCollection('documents');

                // Move new media from rider to riderDocument
                foreach ($mediaItems as $media) {
                    $media->move($riderDocument, 'documents');
                }

                // Clean up temporary collection
                $rider->clearMediaCollection($collectionName);
            } elseif (empty($files) || $files === [null] || (is_array($files) && empty(array_filter($files)))) {
                // If file was removed
                $riderDocument->clearMediaCollection('documents');
            }
        }
    }
}
