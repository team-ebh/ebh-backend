<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Company;
use App\Models\Rider;
use App\Models\RiderAccessibilityCertification;
use App\Models\RiderDocument;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rider Aggregate Trait
 *
 * Contains all relationship methods for the Rider model
 */
trait RiderAggregate
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, Rider::COLUMN_COMPANY_ID);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RiderDocument::class, RiderDocument::COLUMN_RIDER_ID);
    }

    public function accessibilityCertifications(): HasMany
    {
        return $this->hasMany(
            RiderAccessibilityCertification::class,
            RiderAccessibilityCertification::COLUMN_RIDER_ID
        );
    }
}
