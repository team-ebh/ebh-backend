<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Customer\CustomerStatusEnum;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            Customer::COLUMN_FIRST_NAME => fake()->firstName(),
            Customer::COLUMN_LAST_NAME => fake()->lastName(),
            Customer::COLUMN_EMAIL => fake()->optional()->email(),
            Customer::COLUMN_PHONE_NUMBER => fake()->unique()->phoneNumber(),
            Customer::COLUMN_OTP => null,
            Customer::COLUMN_OTP_EXPIRES_AT => null,
            Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
        ];
    }

    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            Customer::COLUMN_STATUS => CustomerStatusEnum::PENDING_VERIFICATION,
        ]);
    }

    //    public function blocked(): static
    //    {
    //        return $this->state(fn (array $attributes) => [
    //            Customer::COLUMN_STATUS => CustomerStatusEnum::BLOCKED,
    //        ]);
    //    }

    public function withOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            Customer::COLUMN_OTP => fake()->numerify('######'),
            Customer::COLUMN_OTP_EXPIRES_AT => now()->addMinutes(5),
        ]);
    }
}
