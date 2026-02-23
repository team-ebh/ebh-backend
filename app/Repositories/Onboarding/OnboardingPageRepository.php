<?php

declare(strict_types=1);

namespace App\Repositories\Onboarding;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Interfaces\Repositories\Onboarding\OnboardingPageRepositoryInterface;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;

class OnboardingPageRepository implements OnboardingPageRepositoryInterface
{
    public function getEnabledByApplicationType(OnboardingApplicationTypeEnum $type): ?OnboardingPage
    {
        return OnboardingPage::query()
            ->select([
                OnboardingPage::COLUMN_ID,
                OnboardingPage::COLUMN_APPLICATION_TYPE,
            ])
            ->with([
                'banners' => fn ($query) => $query
                    ->select([
                        OnboardingPageBanner::COLUMN_ID,
                        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID,
                        OnboardingPageBanner::COLUMN_TITLE,
                        OnboardingPageBanner::COLUMN_TITLE_AR,
                        OnboardingPageBanner::COLUMN_SUBTITLE,
                        OnboardingPageBanner::COLUMN_SUBTITLE_AR,
                        OnboardingPageBanner::COLUMN_SORT,
                    ])
                    ->with('media'),
            ])
            ->where(OnboardingPage::COLUMN_ENABLED, true)
            ->where(OnboardingPage::COLUMN_APPLICATION_TYPE, $type->value)
            ->first();
    }
}
