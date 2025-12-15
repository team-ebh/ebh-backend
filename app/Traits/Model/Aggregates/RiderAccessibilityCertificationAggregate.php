<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Rider;
use App\Models\RiderAccessibilityCertification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RiderAccessibilityCertification Aggregate Trait
 *
 * Contains all relationship methods for the RiderAccessibilityCertification model
 */
trait RiderAccessibilityCertificationAggregate
{
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, RiderAccessibilityCertification::COLUMN_RIDER_ID);
    }
}
