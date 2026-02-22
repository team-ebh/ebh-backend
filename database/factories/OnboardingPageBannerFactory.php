<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingPageBanner>
 */
class OnboardingPageBannerFactory extends Factory
{
    protected $model = OnboardingPageBanner::class;

    public function definition(): array
    {
        return [
            OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => OnboardingPage::factory(),
            OnboardingPageBanner::COLUMN_TITLE => $this->faker->sentence(4),
            OnboardingPageBanner::COLUMN_TITLE_AR => $this->faker->sentence(4),
            OnboardingPageBanner::COLUMN_SUBTITLE => $this->faker->sentence(10),
            OnboardingPageBanner::COLUMN_SUBTITLE_AR => $this->faker->sentence(10),
            OnboardingPageBanner::COLUMN_SORT => $this->faker->unique()->numberBetween(1, 1000),
        ];
    }
}
