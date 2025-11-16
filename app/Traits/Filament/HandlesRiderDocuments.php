<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use App\Models\Document;
use App\Models\Rider;
use App\Models\RiderDocument;

/**
 * Handles Rider Documents upload and management
 *
 * This trait provides functionality to handle document uploads for riders
 * by moving media from temporary collections on Rider to RiderDocument model
 */
trait HandlesRiderDocuments
{
    protected array $documentsData = [];

    /**
     * Handle documents for a rider
     *
     * Creates or updates RiderDocument records and moves media from
     * temporary collections on Rider to the RiderDocument model
     */
    protected function handleDocuments(Rider $rider): void
    {
        // Get all enabled documents
        $enabledDocuments = Document::query()
            ->where(Document::COLUMN_ENABLED, true)
            ->get();

        foreach ($enabledDocuments as $document) {
            $documentData = $this->documentsData[$document->id] ?? [];

            // Check if media exists in temporary collection
            $temporaryCollectionName = "document_{$document->id}";
            $temporaryMedia = $rider->getMedia($temporaryCollectionName);

            // Check if there's any media for this document
            if ($temporaryMedia->isNotEmpty()) {
                // Create or update RiderDocument
                $riderDocument = RiderDocument::query()->updateOrCreate(
                    [
                        RiderDocument::COLUMN_RIDER_ID => $rider->id,
                        RiderDocument::COLUMN_DOCUMENT_ID => $document->id,
                    ]
                );

                // Handle media from temporary collection on rider
                if ($temporaryMedia->isNotEmpty()) {
                    // Clear existing media on RiderDocument
                    $riderDocument->clearMediaCollection(RiderDocument::MEDIA_COLLECTION_NAME);

                    // Move media from rider to rider document
                    foreach ($temporaryMedia as $media) {
                        $media->move($riderDocument, RiderDocument::MEDIA_COLLECTION_NAME);
                    }
                }

                // Clean up temporary collection on rider
                $rider->clearMediaCollection($temporaryCollectionName);
            }
        }
    }

    /**
     * Prepare document data before filling the form (for edit mode)
     *
     * Loads existing RiderDocument media into temporary collections on Rider
     * so they can be displayed in the form
     */
    protected function prepareDocumentsForForm(Rider $rider, array &$data): void
    {
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
                // Each document has its own collection: document_{document_id}
                $temporaryCollectionName = "document_{$document->id}";
                $media = $riderDocument->getMedia(RiderDocument::MEDIA_COLLECTION_NAME);

                if ($media->isNotEmpty()) {
                    foreach ($media as $mediaItem) {
                        try {
                            $rider->addMediaFromDisk($mediaItem->getPath(), $mediaItem->disk)
                                ->usingName($mediaItem->name)
                                ->usingFileName($mediaItem->file_name)
                                ->toMediaCollection($temporaryCollectionName);
                        } catch (\Exception $e) {
                            if (file_exists($mediaItem->getPath())) {
                                $rider->addMedia($mediaItem->getPath())
                                    ->usingName($mediaItem->name)
                                    ->usingFileName($mediaItem->file_name)
                                    ->toMediaCollection($temporaryCollectionName);
                            }
                        }
                    }
                }

                $data['documents'][$document->id] = [];
            }
        }
    }
}
