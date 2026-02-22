<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OnboardingPage>
 */
class OnboardingPageFactory extends Factory
{
    protected $model = OnboardingPage::class;

    public function definition(): array
    {
        return [
            OnboardingPage::COLUMN_NAME => $this->faker->words(3, true),
            OnboardingPage::COLUMN_APPLICATION_TYPE => $this->faker->randomElement(OnboardingApplicationTypeEnum::cases())->value,
            OnboardingPage::COLUMN_ENABLED => false,
        ];
    }

    public function enabled(): static
    {
        return $this->state([OnboardingPage::COLUMN_ENABLED => true]);
    }

    public function forCustomer(): static
    {
        return $this->state([OnboardingPage::COLUMN_APPLICATION_TYPE => OnboardingApplicationTypeEnum::CUSTOMER->value]);
    }

    public function forRider(): static
    {
        return $this->state([OnboardingPage::COLUMN_APPLICATION_TYPE => OnboardingApplicationTypeEnum::RIDER->value]);
    }
}
