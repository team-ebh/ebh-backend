<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Onboarding;

use App\Models\OnboardingPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OnboardingPage $onboardingPage */
        $onboardingPage = $this->resource;

        return [
            /**
             * List of onboarding banners ordered by sort
             *
             * @var array<OnboardingBannerResource>
             */
            'banners' => OnboardingBannerResource::collection($onboardingPage->banners),
        ];
    }
}
