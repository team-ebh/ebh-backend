<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\RiderDocument;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Document Aggregate Trait
 *
 * Contains all relationship methods for the Document model
 */
trait DocumentAggregate
{
    public function riderDocuments(): HasMany
    {
        return $this->hasMany(RiderDocument::class, RiderDocument::COLUMN_DOCUMENT_ID);
    }
}
