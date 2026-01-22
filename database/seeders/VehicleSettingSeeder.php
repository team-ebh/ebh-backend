<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApplicationEnvironmentEnum;
use App\Models\VehicleSetting;
use Illuminate\Database\Seeder;

class VehicleSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run on local, dev, and stage environments
        if (! ApplicationEnvironmentEnum::isLocalEnvironments()) {
            $this->command->info('VehicleSettingSeeder skipped - not running in local, dev, or stage environment.');

            return;
        }

        $this->seedCarTypes();
        $this->seedCarColors();
        $this->seedPassengerCapacity();
        $this->seedCarMakes();
        $this->seedCarModels();

        $this->command->info('Vehicle settings seeded successfully!');
    }

    private function seedCarTypes(): void
    {
        $items = [
            ['name' => 'Sedan', 'name_ar' => 'سيدان'],
            ['name' => 'SUV', 'name_ar' => 'دفع رباعي'],
            ['name' => 'Van', 'name_ar' => 'فان'],
        ];

        foreach ($items as $index => $item) {
            VehicleSetting::query()->updateOrCreate(
                [
                    VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_TYPES,
                    VehicleSetting::COLUMN_NAME => $item['name'],
                ],
                [
                    VehicleSetting::COLUMN_NAME_AR => $item['name_ar'],
                    VehicleSetting::COLUMN_ORDER => $index,
                ]
            );
        }
    }

    private function seedCarColors(): void
    {
        $items = [
            ['name' => 'White', 'name_ar' => 'أبيض'],
            ['name' => 'Black', 'name_ar' => 'أسود'],
            ['name' => 'Silver', 'name_ar' => 'فضي'],
            ['name' => 'Gray', 'name_ar' => 'رمادي'],
            ['name' => 'Blue', 'name_ar' => 'أزرق'],
            ['name' => 'Red', 'name_ar' => 'أحمر'],
            ['name' => 'Gold', 'name_ar' => 'ذهبي'],
            ['name' => 'Beige', 'name_ar' => 'بيج'],
            ['name' => 'Brown', 'name_ar' => 'بني'],
            ['name' => 'Green', 'name_ar' => 'أخضر'],
            ['name' => 'Yellow', 'name_ar' => 'أصفر'],
            ['name' => 'Orange', 'name_ar' => 'برتقالي'],
        ];

        foreach ($items as $index => $item) {
            VehicleSetting::query()->updateOrCreate(
                [
                    VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_COLORS,
                    VehicleSetting::COLUMN_NAME => $item['name'],
                ],
                [
                    VehicleSetting::COLUMN_NAME_AR => $item['name_ar'],
                    VehicleSetting::COLUMN_ORDER => $index,
                ]
            );
        }
    }

    private function seedPassengerCapacity(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            VehicleSetting::query()->updateOrCreate(
                [
                    VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_PASSENGER_CAPACITY,
                    VehicleSetting::COLUMN_CAPACITY => $i,
                ],
                [
                    VehicleSetting::COLUMN_NAME => null,
                    VehicleSetting::COLUMN_NAME_AR => null,
                    VehicleSetting::COLUMN_ORDER => $i - 1,
                ]
            );
        }
    }

    private function seedCarMakes(): void
    {
        $items = [
            ['name' => 'Toyota', 'name_ar' => 'تويوتا'],
            ['name' => 'Honda', 'name_ar' => 'هوندا'],
            ['name' => 'Ford', 'name_ar' => 'فورد'],
        ];

        foreach ($items as $index => $item) {
            VehicleSetting::query()->updateOrCreate(
                [
                    VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MAKES,
                    VehicleSetting::COLUMN_NAME => $item['name'],
                ],
                [
                    VehicleSetting::COLUMN_NAME_AR => $item['name_ar'],
                    VehicleSetting::COLUMN_ORDER => $index,
                ]
            );
        }
    }

    private function seedCarModels(): void
    {
        $items = [
            ['name' => 'Camry', 'name_ar' => 'كامري'],
            ['name' => 'Accord', 'name_ar' => 'أكورد'],
            ['name' => 'Explorer', 'name_ar' => 'إكسبلورر'],
        ];

        foreach ($items as $index => $item) {
            VehicleSetting::query()->updateOrCreate(
                [
                    VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MODELS,
                    VehicleSetting::COLUMN_NAME => $item['name'],
                ],
                [
                    VehicleSetting::COLUMN_NAME_AR => $item['name_ar'],
                    VehicleSetting::COLUMN_ORDER => $index,
                ]
            );
        }
    }
}
