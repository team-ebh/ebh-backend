<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rider>
 */
class RiderFactory extends Factory
{
    protected $model = Rider::class;

    public function definition(): array
    {
        return [
            Rider::COLUMN_FULL_NAME => fake()->name(),
            Rider::COLUMN_EMAIL => fake()->unique()->safeEmail(),
            Rider::COLUMN_PHONE_NUMBER => fake()->unique()->numerify('########'), // 8-digit phone number
            Rider::COLUMN_COMPANY_ID => null,
            Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
            Rider::COLUMN_ENABLED => true,
            Rider::COLUMN_OTP => null,
            Rider::COLUMN_OTP_EXPIRES_AT => null,
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
    }

    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            Rider::COLUMN_STATUS => RiderStatusEnum::BUSY,
        ]);
    }

    public function withOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            Rider::COLUMN_OTP => fake()->numerify('####'),
            Rider::COLUMN_OTP_EXPIRES_AT => now()->addMinutes(5),
        ]);
    }
}
