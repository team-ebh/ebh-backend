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
        if (ApplicationEnvironmentEnum::isProduction()) {
            $this->command->info('VehicleSettingSeeder skipped - not running in local, dev, or stage environment.');

            return;
        }

        $this->seedCarTypes();
        $this->seedCarColors();
        $this->seedPassengerCapacity();
        $this->seedCarMakes();
        $this->seedCarModels();
        $this->seedVehicleTypes();

        $this->command->info('Vehicle settings seeded successfully!');
    }

    private function seedCarTypes(): void
    {
        $items = [
            ['name' => 'Sedan', 'name_ar' => 'سيدان'],
            ['name' => 'SUV', 'name_ar' => 'دفع رباعي'],
            ['name' => 'Van', 'name_ar' => 'فان'],
        ];

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_TYPES, $items);
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

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_COLORS, $items);
    }

    private function seedPassengerCapacity(): void
    {
        $items = [];
        for ($i = 1; $i <= 6; $i++) {
            $items[] = [
                'name' => null,
                'name_ar' => null,
                'capacity' => $i,
            ];
        }

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_PASSENGER_CAPACITY, $items);
    }

    private function seedCarMakes(): void
    {
        $items = [
            ['name' => 'Toyota', 'name_ar' => 'تويوتا'],
            ['name' => 'Honda', 'name_ar' => 'هوندا'],
            ['name' => 'Ford', 'name_ar' => 'فورد'],
        ];

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_MAKES, $items);
    }

    private function seedCarModels(): void
    {
        $items = [
            ['name' => 'Camry', 'name_ar' => 'كامري'],
            ['name' => 'Accord', 'name_ar' => 'أكورد'],
            ['name' => 'Explorer', 'name_ar' => 'إكسبلورر'],
        ];

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_MODELS, $items);
    }

    private function seedVehicleTypes(): void
    {
        $items = [
            ['name' => 'Bed/Stretcher', 'name_ar' => 'سرير/نقالة'],
            ['name' => 'Oxygen Equipment', 'name_ar' => 'معدات الأكسجين'],
            ['name' => 'Mobility Aid', 'name_ar' => 'مساعدات التنقل'],
        ];

        VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_VEHICLE_TYPES, $items);
    }
}
