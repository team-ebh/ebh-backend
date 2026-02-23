<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Onboarding;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Interfaces\Repositories\Onboarding\OnboardingPageRepositoryInterface;
use App\Models\OnboardingPage;

class GetOnboardingPageAction
{
    public function __construct(
        protected OnboardingPageRepositoryInterface $onboardingPageRepository,
    ) {}

    public function __invoke(): ?OnboardingPage
    {
        return $this->onboardingPageRepository->getEnabledByApplicationType(
            OnboardingApplicationTypeEnum::RIDER
        );
    }
}
