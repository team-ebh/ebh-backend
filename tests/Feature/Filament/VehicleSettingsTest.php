<?php

declare(strict_types=1);

use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Filament\Notifications\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    adminPanelLogin();
});

test('can access vehicle settings page', function () {
    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->assertOk();
});

test('can delete unused vehicle setting', function () {
    // Arrange - Create a car type that is not used
    $carType = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_TYPES,
        name: 'Test Car Type',
        nameAr: 'نوع سيارة اختبار'
    );

    // Get all car types except the one we want to delete
    $remainingCarTypes = VehicleSetting::getByType(VehicleSetting::TYPE_CAR_TYPES)
        ->where('id', '!==', $carType->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->{VehicleSetting::COLUMN_NAME},
            'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
        ])
        ->toArray();

    // Act - Save without the car type (which should delete it)
    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'car_types' => $remainingCarTypes,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert - Verify the car type was deleted
    expect(VehicleSetting::find($carType->id))->toBeNull();
});

test('cannot delete vehicle setting that is in use by a rider', function () {
    // Arrange - Create a rider with a vehicle that uses a specific car type
    $carType = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_TYPES,
        name: 'Sedan',
        nameAr: 'سيدان'
    );

    $rider = Rider::factory()->create();
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->id,
        Vehicle::COLUMN_CAR_TYPE_ID => $carType->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    // Get all car types except the one that is in use
    $remainingCarTypes = VehicleSetting::getByType(VehicleSetting::TYPE_CAR_TYPES)
        ->where('id', '!==', $carType->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->{VehicleSetting::COLUMN_NAME},
            'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
        ])
        ->toArray();

    // Act - Try to save without the car type (which should fail)
    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'car_types' => $remainingCarTypes,
        ])
        ->call('save');

    // Assert - Verify the car type was NOT deleted
    expect(VehicleSetting::find($carType->id))->not->toBeNull();

    // Assert - Verify notification was sent
    Notification::assertNotified();
});

test('cannot delete car color that is in use', function () {
    // Arrange
    $carColor = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_COLORS,
        name: 'Red',
        nameAr: 'أحمر'
    );

    $rider = Rider::factory()->create();
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->id,
        Vehicle::COLUMN_CAR_COLOR_ID => $carColor->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    // Act - Try to delete the car color
    $remainingColors = VehicleSetting::getByType(VehicleSetting::TYPE_CAR_COLORS)
        ->where('id', '!==', $carColor->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->{VehicleSetting::COLUMN_NAME},
            'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
        ])
        ->toArray();

    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'car_colors' => $remainingColors,
        ])
        ->call('save');

    // Assert - Verify the car color was NOT deleted
    expect(VehicleSetting::find($carColor->id))->not->toBeNull();
});

test('cannot delete car make that is in use', function () {
    // Arrange
    $carMake = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_MAKES,
        name: 'Toyota',
        nameAr: 'تويوتا'
    );

    $rider = Rider::factory()->create();
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->id,
        Vehicle::COLUMN_CAR_MAKE_ID => $carMake->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    // Act - Try to delete the car make
    $remainingMakes = VehicleSetting::getByType(VehicleSetting::TYPE_CAR_MAKES)
        ->where('id', '!==', $carMake->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->{VehicleSetting::COLUMN_NAME},
            'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
        ])
        ->toArray();

    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'car_makes' => $remainingMakes,
        ])
        ->call('save');

    // Assert - Verify the car make was NOT deleted
    expect(VehicleSetting::find($carMake->id))->not->toBeNull();
});

test('cannot delete car model that is in use', function () {
    // Arrange
    $carModel = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_MODELS,
        name: 'Camry',
        nameAr: 'كامري'
    );

    $rider = Rider::factory()->create();
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->id,
        Vehicle::COLUMN_CAR_MODEL_ID => $carModel->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    // Act - Try to delete the car model
    $remainingModels = VehicleSetting::getByType(VehicleSetting::TYPE_CAR_MODELS)
        ->where('id', '!==', $carModel->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->{VehicleSetting::COLUMN_NAME},
            'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
        ])
        ->toArray();

    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'car_models' => $remainingModels,
        ])
        ->call('save');

    // Assert - Verify the car model was NOT deleted
    expect(VehicleSetting::find($carModel->id))->not->toBeNull();
});

test('cannot delete passenger capacity that is in use', function () {
    // Arrange
    $passengerCapacity = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_PASSENGER_CAPACITY,
        name: '',
        nameAr: '',
        capacity: 4
    );

    $rider = Rider::factory()->create();
    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider->id,
        Vehicle::COLUMN_PASSENGER_CAPACITY_ID => $passengerCapacity->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    // Act - Try to delete the passenger capacity
    $remainingCapacities = VehicleSetting::getByType(VehicleSetting::TYPE_PASSENGER_CAPACITY)
        ->where('id', '!==', $passengerCapacity->id)
        ->map(fn ($item) => [
            'id' => $item->id,
            'capacity' => $item->{VehicleSetting::COLUMN_CAPACITY},
        ])
        ->toArray();

    livewire(\App\Filament\Pages\VehicleSettings::class)
        ->fillForm([
            'passenger_capacity' => $remainingCapacities,
        ])
        ->call('save');

    // Assert - Verify the passenger capacity was NOT deleted
    expect(VehicleSetting::find($passengerCapacity->id))->not->toBeNull();
});

test('error message contains the correct item name and count', function () {
    // Arrange - Create multiple vehicles using the same car type
    $carType = VehicleSetting::createItem(
        type: VehicleSetting::TYPE_CAR_TYPES,
        name: 'Luxury Sedan',
        nameAr: 'سيدان فاخر'
    );

    $rider1 = Rider::factory()->create();
    $rider2 = Rider::factory()->create();

    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider1->id,
        Vehicle::COLUMN_CAR_TYPE_ID => $carType->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'ABC123',
    ]);

    Vehicle::create([
        Vehicle::COLUMN_RIDER_ID => $rider2->id,
        Vehicle::COLUMN_CAR_TYPE_ID => $carType->id,
        Vehicle::COLUMN_PLATE_NUMBER => 'XYZ789',
    ]);

    // Act - Try to delete the car type
    try {
        VehicleSetting::validateNotInUse($carType->id, VehicleSetting::TYPE_CAR_TYPES);
        $exceptionThrown = false;
    } catch (\Exception $e) {
        $exceptionThrown = true;
        $errorMessage = $e->getMessage();
    }

    // Assert
    expect($exceptionThrown)->toBeTrue()
        ->and($errorMessage)->toContain('Luxury Sedan')
        ->and($errorMessage)->toContain('2');
});
