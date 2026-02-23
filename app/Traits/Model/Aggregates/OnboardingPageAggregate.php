<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\OnboardingPageBanner;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OnboardingPage Aggregate Trait
 *
 * Contains all relationship methods for the OnboardingPage model
 */
trait OnboardingPageAggregate
{
    public function banners(): HasMany
    {
        return $this->hasMany(OnboardingPageBanner::class, OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID)
            ->orderBy(OnboardingPageBanner::COLUMN_SORT);
    }
}
