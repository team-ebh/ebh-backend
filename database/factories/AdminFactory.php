<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin;
use App\Traits\Factory\HasEnabledStateTrait;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Random\RandomException;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    use HasEnabledStateTrait;

    protected $model = Admin::class;

    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            Admin::COLUMN_NAME => $this->faker->name(),
            Admin::COLUMN_EMAIL => $this->faker->unique()->safeEmail(),
            Admin::COLUMN_PASSWORD => Hash::make('password'),
            Admin::COLUMN_PHONE_NUMBER => (string) random_int(11111111, 99999999),
        ];
    }
}
