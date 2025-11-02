<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Document;
use App\Models\Rider;
use App\Models\RiderDocument;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RiderDocument Aggregate Trait
 *
 * Contains all relationship methods for the RiderDocument model
 */
trait RiderDocumentAggregate
{
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, RiderDocument::COLUMN_RIDER_ID);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, RiderDocument::COLUMN_DOCUMENT_ID);
    }
}
