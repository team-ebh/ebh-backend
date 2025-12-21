<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Database\Seeder;

class TripSeeder extends Seeder
{
    /**
     * Sample locations for Kuwait
     */
    private array $locations = [
        [
            'title' => 'Kuwait International Airport',
            'sub_title' => 'Terminal 1, Departures',
            'lat' => 29.2263,
            'lng' => 47.9689,
        ],
        [
            'title' => 'Kuwait Towers',
            'sub_title' => 'Arabian Gulf Street',
            'lat' => 29.3759,
            'lng' => 47.9774,
        ],
        [
            'title' => 'The Avenues Mall',
            'sub_title' => 'Al Rai, Fifth Ring Road',
            'lat' => 29.3021,
            'lng' => 47.9307,
        ],
        [
            'title' => 'Al Hamra Tower',
            'sub_title' => 'Sharq, Kuwait City',
            'lat' => 29.3797,
            'lng' => 47.9931,
        ],
        [
            'title' => 'Sabah Al-Salem University City',
            'sub_title' => 'Shadadiya',
            'lat' => 29.2931,
            'lng' => 48.0733,
        ],
        [
            'title' => 'Jahra Hospital',
            'sub_title' => 'Al Jahra',
            'lat' => 29.3375,
            'lng' => 47.6581,
        ],
        [
            'title' => 'Marina Mall',
            'sub_title' => 'Salmiya',
            'lat' => 29.3394,
            'lng' => 48.0778,
        ],
        [
            'title' => 'Al Shaheed Park',
            'sub_title' => 'Kuwait City',
            'lat' => 29.3685,
            'lng' => 47.9735,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTripsForAllStatuses();
    }

    /**
     * Create trips for all statuses
     */
    public function createTripsForAllStatuses(): void
    {
        // Create customers and riders for the trips
        $customers = Customer::factory()->count(10)->create();
        $riders = Rider::factory()->count(5)->create();

        // Define trip statuses (excluding DRAFT)
        $statuses = [
            TripStatusEnum::PENDING_RIDER,
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ON_TRIP,
            TripStatusEnum::COMPLETED,
            TripStatusEnum::CANCELED_BY_CUSTOMER,
            TripStatusEnum::CANCELLED_BY_RIDER,
        ];

        foreach ($statuses as $status) {
            $count = fake()->numberBetween(3, 6);
            $this->createTripsForStatus($status, $count, $customers, $riders);
        }

        $this->command->info('Successfully created trips for all statuses!');
        $this->command->info('Total trips created: ' . Trip::query()->count());
    }

    /**
     * Create trips for a specific status
     */
    public function createTripsForStatus(
        TripStatusEnum $status,
        int $count = 3,
        $customers = null,
        $riders = null
    ): void {
        // If customers/riders not provided, get existing ones or create new
        if ($customers === null) {
            $customers = Customer::all();
            if ($customers->isEmpty()) {
                $customers = Customer::factory()->count(10)->create();
            }
        }

        if ($riders === null) {
            $riders = Rider::all();
            if ($riders->isEmpty()) {
                $riders = Rider::factory()->count(5)->create();
            }
        }

        $needsRider = in_array($status, [
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ON_TRIP,
            TripStatusEnum::COMPLETED,
            TripStatusEnum::CANCELLED_BY_RIDER,
        ]);

        for ($i = 0; $i < $count; $i++) {
            // Get a customer without active trip
            $customer = $this->getAvailableCustomer($customers);

            // Get an online rider if needed
            $rider = $needsRider ? $this->getOnlineRider($riders) : $riders->random();

            // Create trip
            $trip = Trip::create([
                Trip::COLUMN_CUSTOMER_ID => $customer->id,
                Trip::COLUMN_RIDER_ID => $needsRider ? $rider->id : null,
                Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
                Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
                Trip::COLUMN_PASSENGER_COUNT => fake()->numberBetween(1, 4),
                Trip::COLUMN_ACCESSIBILITY_PRICE => fake()->boolean() ? fake()->numberBetween(5, 15) : 0,
                Trip::COLUMN_WAITING_PRICE => 0,
                Trip::COLUMN_TOTAL_PRICE => fake()->numberBetween(10, 50),
                Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
                Trip::COLUMN_PAYMENT_METHOD => fake()->boolean()
                    ? PaymentMethodEnum::CASH->value
                    : PaymentMethodEnum::KNET->value,
                Trip::COLUMN_STATUS => $status->value,
            ]);

            // Create origin location (ONE_WAY has only 1 origin)
            $originLocation = fake()->randomElement($this->locations);
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => $originLocation['title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $originLocation['sub_title'],
                TripLocation::COLUMN_LATITUDE => $originLocation['lat'],
                TripLocation::COLUMN_LONGITUDE => $originLocation['lng'],
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::ORIGIN->value,
                TripLocation::COLUMN_SEQUENCE => 1,
                TripLocation::COLUMN_STATUS => $this->getLocationStatus($status, true),
            ]);

            // Create destination location (ONE_WAY has only 1 destination)
            $destinationLocation = fake()->randomElement($this->locations);
            TripLocation::create([
                TripLocation::COLUMN_TRIP_ID => $trip->id,
                TripLocation::COLUMN_LOCATION_TITLE => $destinationLocation['title'],
                TripLocation::COLUMN_LOCATION_SUB_TITLE => $destinationLocation['sub_title'],
                TripLocation::COLUMN_LATITUDE => $destinationLocation['lat'],
                TripLocation::COLUMN_LONGITUDE => $destinationLocation['lng'],
                TripLocation::COLUMN_TYPE => TripLocationTypeEnum::DESTINATION->value,
                TripLocation::COLUMN_SEQUENCE => 1,
                TripLocation::COLUMN_STATUS => $this->getLocationStatus($status, false),
            ]);
        }

        $this->command->info("Created {$count} trips with status: {$status->getLabel()}");
    }

    /**
     * Get appropriate location status based on trip status
     */
    private function getLocationStatus(TripStatusEnum $tripStatus, bool $isOrigin): int
    {
        return match ($tripStatus) {
            TripStatusEnum::ON_TRIP => $isOrigin
                ? TripLocationStatusEnum::PICKED_UP->value
                : TripLocationStatusEnum::PENDING->value,
            TripStatusEnum::COMPLETED => TripLocationStatusEnum::COMPLETED->value,
            default => TripLocationStatusEnum::PENDING->value,
        };
    }

    /**
     * Get a customer without active trip
     * If none found, create a new customer
     */
    private function getAvailableCustomer($customers): Customer
    {
        // Try to find a customer without active trips
        foreach ($customers as $customer) {
            $hasActiveTrip = Trip::query()
                ->where(Trip::COLUMN_CUSTOMER_ID, $customer->id)
                ->activeTrips()
                ->exists();

            if (! $hasActiveTrip) {
                return $customer;
            }
        }

        // If no available customer found, create a new one
        $newCustomer = Customer::factory()->create();
        $this->command->info("Created new customer (ID: {$newCustomer->id}) - No available customers without active trips");

        return $newCustomer;
    }

    /**
     * Get an online rider
     * If none found, create a new rider with ONLINE status
     */
    private function getOnlineRider($riders): Rider
    {
        // Try to find an online rider
        $onlineRider = $riders->first(fn ($rider) => $rider->isOnline());

        if ($onlineRider) {
            return $onlineRider;
        }

        // If no online rider found, create a new one with ONLINE status
        $newRider = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);

        $this->command->info("Created new online rider (ID: {$newRider->id}) - No online riders available");

        return $newRider;
    }
}
