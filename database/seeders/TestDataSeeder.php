<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApplicationEnvironmentEnum;
use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Test phone numbers for consistent testing
     */
    private const string TEST_CUSTOMER_PHONE = '65656565';

    private const string TEST_RIDER_PHONE = '65656565';

    /**
     * Run the database seeds.
     * Only runs in local/testing environments
     */
    public function run(): void
    {
        // Only run in local/testing environment
        if (! ApplicationEnvironmentEnum::isLocalEnvironments()) {
            $this->command->warn('TestDataSeeder only runs in local/testing environment');

            return;
        }

        $this->command->info('🌱 Starting TestDataSeeder...');

        $this->seedCompanies();
        $this->seedCustomers();
        $this->seedRiders();
        $this->seedVehicleSettings();
        $this->seedDocuments();
        $this->seedVehicles();

        $this->command->info('✅ TestDataSeeder completed successfully!');
    }

    /**
     * Seed test companies
     */
    private function seedCompanies(): void
    {
        $this->command->info('📦 Seeding companies...');

        $companies = [
            [
                'unique_key' => '50001001',
                'data' => [
                    Company::COLUMN_NAME => 'Test Company 1',
                    Company::COLUMN_EMAIL => 'company1@test.com',
                    Company::COLUMN_PHONE_NUMBER => '50001001',
                    Company::COLUMN_ADDRESS => 'Test Address 1, Kuwait City',
                    Company::COLUMN_COMMISSION_RATE => 15.00,
                ],
            ],
            [
                'unique_key' => '50001002',
                'data' => [
                    Company::COLUMN_NAME => 'Test Company 2',
                    Company::COLUMN_EMAIL => 'company2@test.com',
                    Company::COLUMN_PHONE_NUMBER => '50001002',
                    Company::COLUMN_ADDRESS => 'Test Address 2, Salmiya',
                    Company::COLUMN_COMMISSION_RATE => 20.00,
                ],
            ],
        ];

        foreach ($companies as $company) {
            Company::query()->updateOrCreate(
                [Company::COLUMN_PHONE_NUMBER => $company['unique_key']],
                $company['data']
            );
        }

        $this->command->info('✓ Companies seeded');
    }

    /**
     * Seed test customers
     */
    private function seedCustomers(): void
    {
        $this->command->info('👥 Seeding customers...');

        $customers = [
            [
                'unique_key' => self::TEST_CUSTOMER_PHONE,
                'data' => [
                    Customer::COLUMN_FIRST_NAME => 'Test',
                    Customer::COLUMN_LAST_NAME => 'Customer',
                    Customer::COLUMN_EMAIL => 'customer@test.com',
                    Customer::COLUMN_PHONE_NUMBER => self::TEST_CUSTOMER_PHONE,
                    Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
                ],
            ],
            [
                'unique_key' => '50002001',
                'data' => [
                    Customer::COLUMN_FIRST_NAME => 'John',
                    Customer::COLUMN_LAST_NAME => 'Doe',
                    Customer::COLUMN_EMAIL => 'john.doe@test.com',
                    Customer::COLUMN_PHONE_NUMBER => '50002001',
                    Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
                ],
            ],
            [
                'unique_key' => '50002002',
                'data' => [
                    Customer::COLUMN_FIRST_NAME => 'Jane',
                    Customer::COLUMN_LAST_NAME => 'Smith',
                    Customer::COLUMN_EMAIL => 'jane.smith@test.com',
                    Customer::COLUMN_PHONE_NUMBER => '50002002',
                    Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
                ],
            ],
        ];

        foreach ($customers as $customer) {
            Customer::query()->updateOrCreate(
                [Customer::COLUMN_PHONE_NUMBER => $customer['unique_key']],
                $customer['data']
            );
        }

        $this->command->info('✓ Customers seeded');
    }

    /**
     * Seed test riders
     */
    private function seedRiders(): void
    {
        $this->command->info('🏍️  Seeding riders...');

        $company = Company::query()->first();

        if (! $company) {
            $this->command->warn('⚠ No company found, skipping riders');

            return;
        }

        $riders = [
            [
                'unique_key' => self::TEST_RIDER_PHONE,
                'data' => [
                    Rider::COLUMN_FULL_NAME => 'Test Rider',
                    Rider::COLUMN_EMAIL => 'rider@test.com',
                    Rider::COLUMN_PHONE_NUMBER => self::TEST_RIDER_PHONE,
                    Rider::COLUMN_COMPANY_ID => $company->id,
                    Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
                ],
            ],
            [
                'unique_key' => '50003001',
                'data' => [
                    Rider::COLUMN_FULL_NAME => 'Ahmed Hassan',
                    Rider::COLUMN_EMAIL => 'ahmed.hassan@test.com',
                    Rider::COLUMN_PHONE_NUMBER => '50003001',
                    Rider::COLUMN_COMPANY_ID => $company->id,
                    Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
                ],
            ],
            [
                'unique_key' => '50003002',
                'data' => [
                    Rider::COLUMN_FULL_NAME => 'Mohammed Ali',
                    Rider::COLUMN_EMAIL => 'mohammed.ali@test.com',
                    Rider::COLUMN_PHONE_NUMBER => '50003002',
                    Rider::COLUMN_COMPANY_ID => $company->id,
                    Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
                ],
            ],
        ];

        foreach ($riders as $rider) {
            Rider::query()->updateOrCreate(
                [Rider::COLUMN_PHONE_NUMBER => $rider['unique_key']],
                $rider['data']
            );
        }

        $this->command->info('✓ Riders seeded');
    }

    /**
     * Seed vehicle settings if they don't exist
     */
    private function seedVehicleSettings(): void
    {
        $this->command->info('🚗 Seeding vehicle settings...');

        // Check if VehicleSetting seeder exists
        if (class_exists(\Database\Seeders\VehicleSettingSeeder::class)) {
            $this->call(\Database\Seeders\VehicleSettingSeeder::class);
        } else {
            $this->command->warn('⚠ VehicleSettingSeeder not found, skipping');
        }

        $this->command->info('✓ Vehicle settings seeded');
    }

    /**
     * Seed documents for riders
     */
    private function seedDocuments(): void
    {
        $this->command->info('📄 Seeding documents...');

        $documents = [
            [
                'unique_key' => 'Driving License',
                'data' => [
                    Document::COLUMN_NAME => 'Driving License',
                    Document::COLUMN_DESCRIPTION => 'Valid driving license',
                    Document::COLUMN_IS_REQUIRED => true,
                ],
            ],
            [
                'unique_key' => 'National ID',
                'data' => [
                    Document::COLUMN_NAME => 'National ID',
                    Document::COLUMN_DESCRIPTION => 'National identification card',
                    Document::COLUMN_IS_REQUIRED => true,
                ],
            ],
            [
                'unique_key' => 'Vehicle Registration',
                'data' => [
                    Document::COLUMN_NAME => 'Vehicle Registration',
                    Document::COLUMN_DESCRIPTION => 'Vehicle registration certificate',
                    Document::COLUMN_IS_REQUIRED => true,
                ],
            ],
        ];

        foreach ($documents as $document) {
            Document::query()->updateOrCreate(
                [Document::COLUMN_NAME => $document['unique_key']],
                $document['data']
            );
        }

        $this->command->info('✓ Documents seeded');
    }

    /**
     * Seed vehicles for riders (optional - can be added later)
     */
    private function seedVehicles(): void
    {
        $this->command->info('🚙 Seeding vehicles...');

        $rider = Rider::query()->where(Rider::COLUMN_PHONE_NUMBER, self::TEST_RIDER_PHONE)->first();

        if (! $rider) {
            $this->command->warn('⚠ No rider found, skipping vehicles');

            return;
        }

        // Get vehicle settings by type
        $carType = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_CAR_TYPES)
            ->where(VehicleSetting::COLUMN_NAME, 'Sedan')
            ->first();

        $carColor = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_CAR_COLORS)
            ->where(VehicleSetting::COLUMN_NAME, 'White')
            ->first();

        $carMake = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_CAR_MAKES)
            ->where(VehicleSetting::COLUMN_NAME, 'Toyota')
            ->first();

        $carModel = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_CAR_MODELS)
            ->where(VehicleSetting::COLUMN_NAME, 'Camry')
            ->first();

        $passengerCapacity = VehicleSetting::query()
            ->where(VehicleSetting::COLUMN_TYPE, VehicleSetting::TYPE_PASSENGER_CAPACITY)
            ->where(VehicleSetting::COLUMN_CAPACITY, 4)
            ->first();

        if (! $carType || ! $carColor || ! $carMake || ! $carModel || ! $passengerCapacity) {
            $this->command->warn('⚠ Vehicle settings not found, skipping vehicles');

            return;
        }

        Vehicle::query()->updateOrCreate(
            [
                Vehicle::COLUMN_RIDER_ID => $rider->id,
                Vehicle::COLUMN_PLATE_NUMBER => 'TEST-123',
            ],
            [
                Vehicle::COLUMN_CAR_TYPE_ID => $carType->id,
                Vehicle::COLUMN_CAR_COLOR_ID => $carColor->id,
                Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
                Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
                Vehicle::COLUMN_PASSENGER_CAPACITY_ID => $passengerCapacity->id,
                Vehicle::COLUMN_YEAR => 2022,
            ]
        );

        $this->command->info('✓ Vehicles seeded');
    }
}
