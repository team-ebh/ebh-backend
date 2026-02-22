<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\ApplicationEnvironmentEnum;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminInitializerSeeder::class,
            StaticPageSeeder::class,
            OnboardingPageSeeder::class,
        ]);

        if (ApplicationEnvironmentEnum::isLocalEnvironments()) {
            $this->call([
                VehicleSettingSeeder::class,
                TestDataSeeder::class,
                TripSeeder::class,
            ]);
        }
    }
}
