<?php

declare(strict_types=1);

namespace App\Traits\Model\Aggregates;

use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OnboardingPageBanner Aggregate Trait
 *
 * Contains all relationship methods for the OnboardingPageBanner model
 */
trait OnboardingPageBannerAggregate
{
    public function onboardingPage(): BelongsTo
    {
        return $this->belongsTo(OnboardingPage::class, OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID);
    }
}
