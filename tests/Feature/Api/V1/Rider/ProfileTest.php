<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleAccessibilityFeature;
use App\Models\VehicleSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('Get Profile API', function () {
    beforeEach(function () {
        $this->rider = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
    });

    it('rider can get their profile', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'full_name',
                    'email',
                    'phone',
                    'image',
                    'total_rides_count',
                    'rating',
                    'status',
                    'vehicle',
                    'joined_at',
                ],
            ])
            ->assertJsonPath('data.id', $this->rider->id)
            ->assertJsonPath('data.full_name', $this->rider->full_name);
    });

    it('profile includes image field', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonPath('data.image', null);
    });

    it('profile includes total_rides_count', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonPath('data.total_rides_count', 0);
    });

    it('profile includes rating', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonPath('data.rating', 4.6);
    });

    it('profile includes joined_at as timestamp', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonPath('data.joined_at', $this->rider->{Rider::COLUMN_CREATED_AT}->timestamp);
    });

    it('profile includes vehicle when rider has vehicle', function () {
        // Create vehicle settings
        $carType = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_TYPES,
            VehicleSetting::COLUMN_NAME => 'Sedan',
            VehicleSetting::COLUMN_NAME_AR => 'سيدان',
        ]);
        $carColor = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_COLORS,
            VehicleSetting::COLUMN_NAME => 'Black',
            VehicleSetting::COLUMN_NAME_AR => 'أسود',
        ]);
        $passengerCapacity = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_PASSENGER_CAPACITY,
            VehicleSetting::COLUMN_NAME => '4 Passengers',
            VehicleSetting::COLUMN_NAME_AR => '4 ركاب',
            VehicleSetting::COLUMN_CAPACITY => 4,
        ]);
        $carMake = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MAKES,
            VehicleSetting::COLUMN_NAME => 'Toyota',
            VehicleSetting::COLUMN_NAME_AR => 'تويوتا',
        ]);
        $carModel = VehicleSetting::create([
            VehicleSetting::COLUMN_TYPE => VehicleSetting::TYPE_CAR_MODELS,
            VehicleSetting::COLUMN_NAME => 'Camry',
            VehicleSetting::COLUMN_NAME_AR => 'كامري',
        ]);

        // Create vehicle for rider
        $vehicle = Vehicle::create([
            Vehicle::COLUMN_RIDER_ID => $this->rider->id,
            Vehicle::COLUMN_CAR_TYPE_ID => $carType->id,
            Vehicle::COLUMN_CAR_COLOR_ID => $carColor->id,
            Vehicle::COLUMN_PASSENGER_CAPACITY_ID => $passengerCapacity->id,
            Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
            Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
            Vehicle::COLUMN_YEAR => 2023,
            Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
        ]);

        // Add accessibility features
        VehicleAccessibilityFeature::create([
            VehicleAccessibilityFeature::COLUMN_VEHICLE_ID => $vehicle->id,
            VehicleAccessibilityFeature::COLUMN_ACCESSIBILITY_REQUIREMENT_ID => AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->value,
        ]);
        VehicleAccessibilityFeature::create([
            VehicleAccessibilityFeature::COLUMN_VEHICLE_ID => $vehicle->id,
            VehicleAccessibilityFeature::COLUMN_ACCESSIBILITY_REQUIREMENT_ID => AccessibilityRequirementsEnum::PORTABLE_RAMP->value,
        ]);

        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'vehicle' => [
                        'id',
                        'car_type',
                        'car_color',
                        'passenger_capacity',
                        'car_make',
                        'car_model',
                        'model',
                        'year',
                        'plate_number',
                        'accessibility_features' => [
                            '*' => [
                                'id',
                                'label',
                                'description',
                                'icon',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.vehicle.car_type', 'Sedan')
            ->assertJsonPath('data.vehicle.car_color', 'Black')
            ->assertJsonPath('data.vehicle.passenger_capacity', 4)
            ->assertJsonPath('data.vehicle.car_make', 'Toyota')
            ->assertJsonPath('data.vehicle.car_model', 'Camry')
            ->assertJsonPath('data.vehicle.model', 'Toyota Camry')
            ->assertJsonPath('data.vehicle.year', 2023)
            ->assertJsonPath('data.vehicle.plate_number', 'ABC123')
            ->assertJsonCount(2, 'data.vehicle.accessibility_features')
            ->assertJsonPath('data.vehicle.accessibility_features.0.id', AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->value)
            ->assertJsonPath('data.vehicle.accessibility_features.1.id', AccessibilityRequirementsEnum::PORTABLE_RAMP->value);
    });

    it('profile returns null vehicle when rider has no vehicle', function () {
        actingAs($this->rider, 'rider')
            ->getJson(route('v1.riders.profile'))
            ->assertOk()
            ->assertJsonPath('data.vehicle', null);
    });

    it('unauthenticated rider cannot get profile', function () {
        getJson(route('v1.riders.profile'))
            ->assertUnauthorized();
    });
});

describe('Update Profile API', function () {
    beforeEach(function () {
        $this->rider = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
    });

    it('rider can update their profile', function () {
        $newData = [
            'full_name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone_number' => '99887766',
        ];

        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), $newData)
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_FULL_NAME})->toBe('Updated Name')
            ->and($this->rider->{Rider::COLUMN_EMAIL})->toBe('updated@example.com')
            ->and($this->rider->{Rider::COLUMN_PHONE_NUMBER})->toBe('99887766');
    });

    it('rider can update profile without email and keeps existing email', function () {
        // Set an email on the rider first
        $this->rider->update([Rider::COLUMN_EMAIL => 'existing@example.com']);

        $newData = [
            'full_name' => 'Updated Name',
            'phone_number' => '99887766',
        ];

        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), $newData)
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Updated Name')
            ->assertJsonPath('data.email', 'existing@example.com'); // Email unchanged

        $this->rider->refresh();
        expect($this->rider->{Rider::COLUMN_FULL_NAME})->toBe('Updated Name')
            ->and($this->rider->{Rider::COLUMN_EMAIL})->toBe('existing@example.com')
            ->and($this->rider->{Rider::COLUMN_PHONE_NUMBER})->toBe('99887766');
    });

    it('update profile validates required full_name', function () {
        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), [
                'email' => 'test@example.com',
                'phone_number' => '99887766',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'full_name');
    });

    it('update profile validates required phone_number', function () {
        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), [
                'full_name' => 'Test Name',
                'email' => 'test@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');
    });

    it('update profile validates phone_number must be 8 digits', function () {
        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), [
                'full_name' => 'Test Name',
                'email' => 'test@example.com',
                'phone_number' => '123456', // only 6 digits
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');
    });

    it('update profile validates email format', function () {
        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), [
                'full_name' => 'Test Name',
                'email' => 'invalid-email',
                'phone_number' => '99887766',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'email');
    });

    it('update profile validates full_name max length', function () {
        actingAs($this->rider, 'rider')
            ->putJson(route('v1.riders.profile.update'), [
                'full_name' => str_repeat('a', 61), // 61 characters
                'email' => 'test@example.com',
                'phone_number' => '99887766',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'full_name');
    });

    it('unauthenticated rider cannot update profile', function () {
        putJson(route('v1.riders.profile.update'), [
            'full_name' => 'Test',
            'email' => 'test@example.com',
            'phone_number' => '99887766',
        ])
            ->assertUnauthorized();
    });
});

describe('Update Profile Image API', function () {
    beforeEach(function () {
        $this->rider = Rider::factory()->create([
            Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
        ]);
    });

    it('rider can upload profile image', function () {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('profile.jpg', 200, 200);

        actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.profile.image.update'), [
                'image' => $image,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'full_name',
                    'image',
                ],
            ]);

        $this->rider->refresh();
        expect($this->rider->getFirstMediaLink(Rider::PROFILE_PHOTO))->not->toBeNull();
    });

    it('update profile image validates required image', function () {
        actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.profile.image.update'), [])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    it('update profile image validates image format', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.profile.image.update'), [
                'image' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    it('update profile image validates max size', function () {
        $image = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        actingAs($this->rider, 'rider')
            ->postJson(route('v1.riders.profile.image.update'), [
                'image' => $image,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    it('unauthenticated rider cannot update profile image', function () {
        $image = UploadedFile::fake()->image('profile.jpg');

        postJson(route('v1.riders.profile.image.update'), [
            'image' => $image,
        ])
            ->assertUnauthorized();
    });
});
