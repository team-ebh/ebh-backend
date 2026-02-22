<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Onboarding;

use App\Enums\LanguageEnum;
use App\Models\OnboardingPageBanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingBannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var OnboardingPageBanner $banner */
        $banner = $this->resource;

        $isArabic = app()->getLocale() === LanguageEnum::ARABIC->value;

        return [
            /**
             * Banner ID
             *
             * @var int
             *
             * @example 1
             */
            'id' => $banner->{OnboardingPageBanner::COLUMN_ID},

            /**
             * Banner title based on app language
             *
             * @var string
             *
             * @example Go Live and Accept Rides
             */
            'title' => $isArabic
                ? $banner->{OnboardingPageBanner::COLUMN_TITLE_AR}
                : $banner->{OnboardingPageBanner::COLUMN_TITLE},

            /**
             * Banner subtitle based on app language
             *
             * @var string
             *
             * @example Tap the button to go online and start receiving ride requests from passengers in your area.
             */
            'subtitle' => $isArabic
                ? $banner->{OnboardingPageBanner::COLUMN_SUBTITLE_AR}
                : $banner->{OnboardingPageBanner::COLUMN_SUBTITLE},

            /**
             * Banner image URL
             *
             * @var string|null
             *
             * @example https://example.com/images/onboarding/rider-1.png
             */
            'image' => $banner->getFirstMediaLink(OnboardingPageBanner::IMAGE),
        ];
    }
}
