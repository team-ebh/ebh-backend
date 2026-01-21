<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

describe('Get Profile API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create([
            Customer::COLUMN_FIRST_NAME => 'John',
            Customer::COLUMN_LAST_NAME => 'Doe',
            Customer::COLUMN_EMAIL => 'john@example.com',
            Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
        ]);
    });

    test('customer can get their profile', function () {
        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.profile'));

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->customer->{Customer::COLUMN_ID},
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'full_name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => [
                        'code' => '+965',
                        'number' => $this->customer->{Customer::COLUMN_PHONE_NUMBER},
                    ],
                    'status' => [
                        'id' => $this->customer->{Customer::COLUMN_STATUS}->value,
                        'label' => 'Active',
                    ],
                ],
            ]);
    });

    test('profile includes image field', function () {
        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.profile'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'phone',
                    'image',
                    'total_rides_count',
                    'status',
                ],
            ]);

        expect($response->json('data.image'))->toBeNull();
    });

    test('profile includes total_rides_count', function () {
        // Create completed trips for this customer
        for ($i = 0; $i < 3; $i++) {
            Trip::create([
                Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
                Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
                Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
                Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
                Trip::COLUMN_PASSENGER_COUNT => 1,
                Trip::COLUMN_TOTAL_PRICE => 5.000,
                Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
                Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED->value,
            ]);
        }

        // Create a canceled trip (should not count)
        Trip::create([
            Trip::COLUMN_CUSTOMER_ID => $this->customer->id,
            Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
            Trip::COLUMN_RIDE_TYPE => RideTypeEnum::ONE_WAY->value,
            Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
            Trip::COLUMN_PASSENGER_COUNT => 1,
            Trip::COLUMN_TOTAL_PRICE => 5.000,
            Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
            Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER->value,
        ]);

        $response = actingAs($this->customer, 'customer')
            ->getJson(route('v1.customers.profile'));

        $response->assertOk()
            ->assertJsonPath('data.total_rides_count', 3);
    });

    test('unauthenticated customer cannot get profile', function () {
        $response = getJson(route('v1.customers.profile'));

        $response->assertUnauthorized();
    });
});

describe('Update Profile API', function () {
    beforeEach(function () {
        $this->customer = Customer::factory()->create([
            Customer::COLUMN_FIRST_NAME => 'John',
            Customer::COLUMN_LAST_NAME => 'Doe',
            Customer::COLUMN_EMAIL => 'john@example.com',
            Customer::COLUMN_PHONE_NUMBER => '12345678',
            Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
        ]);
    });

    test('customer can update their profile', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'phone_number' => '87654321',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'full_name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'phone' => [
                        'code' => '+965',
                        'number' => '87654321',
                    ],
                ],
            ]);

        $this->customer->refresh();
        expect($this->customer->{Customer::COLUMN_FIRST_NAME})->toBe('Jane')
            ->and($this->customer->{Customer::COLUMN_LAST_NAME})->toBe('Smith')
            ->and($this->customer->{Customer::COLUMN_EMAIL})->toBe('jane@example.com')
            ->and($this->customer->{Customer::COLUMN_PHONE_NUMBER})->toBe('87654321');
    });

    test('customer can update profile without email', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => null,
                'phone_number' => '87654321',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.email', null);
    });

    test('update profile validates required first_name', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'last_name' => 'Smith',
                'phone_number' => '87654321',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'first_name');
    });

    test('update profile validates required last_name', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'phone_number' => '87654321',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'last_name');
    });

    test('update profile validates required phone_number', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');
    });

    test('update profile validates phone_number must be exactly 8 digits', function () {
        // Too short
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone_number' => '1234567', // 7 digits
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');

        // Too long
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone_number' => '123456789', // 9 digits
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');

        // Contains letters
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone_number' => '1234567a',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'phone_number');
    });

    test('update profile validates email format', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'invalid-email',
                'phone_number' => '87654321',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'email');
    });

    test('update profile validates first_name max length', function () {
        $response = actingAs($this->customer, 'customer')
            ->putJson(route('v1.customers.profile.update'), [
                'first_name' => str_repeat('a', 31),
                'last_name' => 'Smith',
                'phone_number' => '87654321',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'first_name');
    });

    test('unauthenticated customer cannot update profile', function () {
        $response = putJson(route('v1.customers.profile.update'), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone_number' => '87654321',
        ]);

        $response->assertUnauthorized();
    });
});

describe('Update Profile Image API', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->customer = Customer::factory()->create([
            Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
        ]);
    });

    test('customer can upload profile image', function () {
        $image = UploadedFile::fake()->image('profile.jpg', 500, 500)->size(1024);

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'image',
                ],
            ]);

        expect($response->json('data.image'))->not->toBeNull();
    });

    test('customer can upload PNG image', function () {
        $image = UploadedFile::fake()->image('profile.png', 500, 500)->size(1024);

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image,
            ]);

        $response->assertOk();
        expect($response->json('data.image'))->not->toBeNull();
    });

    test('customer can upload WebP image', function () {
        $image = UploadedFile::fake()->create('profile.webp', 1024, 'image/webp');

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image,
            ]);

        $response->assertOk();
    });

    test('customer cannot upload image larger than 2MB', function () {
        $image = UploadedFile::fake()->image('profile.jpg', 500, 500)->size(3000); // 3MB

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    test('customer cannot upload non-image file', function () {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    test('customer cannot upload GIF image', function () {
        $image = UploadedFile::fake()->image('profile.gif', 500, 500);

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    test('customer cannot upload SVG image', function () {
        $file = UploadedFile::fake()->create('profile.svg', 1024, 'image/svg+xml');

        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    test('image field is required', function () {
        $response = actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), []);

        $response->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'image');
    });

    test('uploading new image replaces old one', function () {
        // Upload first image
        $image1 = UploadedFile::fake()->image('profile1.jpg', 500, 500)->size(1024);
        actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image1,
            ]);

        $this->customer->refresh();
        expect($this->customer->media)->toHaveCount(1);

        // Upload second image
        $image2 = UploadedFile::fake()->image('profile2.jpg', 500, 500)->size(1024);
        actingAs($this->customer, 'customer')
            ->postJson(route('v1.customers.profile.image.update'), [
                'image' => $image2,
            ]);

        $this->customer->refresh();
        // Should still have only 1 image (old one replaced)
        expect($this->customer->media)->toHaveCount(1);
    });

    test('unauthenticated customer cannot upload profile image', function () {
        $image = UploadedFile::fake()->image('profile.jpg', 500, 500);

        $response = postJson(route('v1.customers.profile.image.update'), [
            'image' => $image,
        ]);

        $response->assertUnauthorized();
    });
});
