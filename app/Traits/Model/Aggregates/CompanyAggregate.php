<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\Company;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company Aggregate Trait
 *
 * Contains all relationship methods for the Company model
 */
trait CompanyAggregate
{
    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class, Rider::COLUMN_COMPANY_ID);
    }
}
