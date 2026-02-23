<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Onboarding;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;

interface OnboardingPageRepositoryInterface
{
    public function getEnabledByApplicationType(OnboardingApplicationTypeEnum $type): ?OnboardingPage;
}
