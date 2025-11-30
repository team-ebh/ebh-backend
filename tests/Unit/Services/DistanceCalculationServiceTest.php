<?php

declare(strict_types=1);

use App\Services\DistanceCalculationService;
use Illuminate\Support\Facades\Config;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new DistanceCalculationService;
});

test('calculates distance between two points using haversine', function () {
    // Temporarily disable API key to force Haversine calculation
    Config::set('services.google_maps.api_key');

    // Kuwait City coordinates (approximately)
    $originLat = 29.3759;
    $originLng = 47.9774;

    // Coordinates approximately 4.5km away
    $destLat = 29.4159;
    $destLng = 47.9774;

    $result = $this->service->calculateDistanceAndDuration(
        $originLat,
        $originLng,
        $destLat,
        $destLng
    );

    // Assert distance is calculated
    expect($result)->toBeArray()
        ->toHaveKeys(['distance_meters', 'estimated_arrival_seconds'])
        ->and($result['distance_meters'])->not->toBeNull()
        ->toBeGreaterThan(4000)
        ->toBeLessThan(50000)
        ->and($result['estimated_arrival_seconds'])->not->toBeNull()
        ->toBeGreaterThan(0);
});

test('calculates zero distance for same location', function () {
    Config::set('services.google_maps.api_key');

    $lat = 29.3759;
    $lng = 47.9774;

    $result = $this->service->calculateDistanceAndDuration($lat, $lng, $lat, $lng);

    expect($result)->toBeArray()
        ->and($result['distance_meters'])->toBe(0)
        ->and($result['estimated_arrival_seconds'])->toBe(0);
});

test('handles large distances', function () {
    Config::set('services.google_maps.api_key');

    // Kuwait to Dubai (approximately 800km)
    $kuwaitLat = 29.3759;
    $kuwaitLng = 47.9774;
    $dubaiLat = 25.2048;
    $dubaiLng = 55.2708;

    $result = $this->service->calculateDistanceAndDuration(
        $kuwaitLat,
        $kuwaitLng,
        $dubaiLat,
        $dubaiLng
    );

    expect($result)->toBeArray()
        ->and($result['distance_meters'])->not->toBeNull()
        ->and($result['distance_meters'])
        ->toBeGreaterThan(750000)
        ->toBeLessThan(1300000)
        ->and($result['estimated_arrival_seconds'])->toBeGreaterThan(0);
});

test('batch calculate multiple pairs', function () {
    Config::set('services.google_maps.api_key');

    $pairs = [
        [
            'origin_latitude' => 29.3759,
            'origin_longitude' => 47.9774,
            'destination_latitude' => 29.4159,
            'destination_longitude' => 47.9774,
        ],
        [
            'origin_latitude' => 29.3759,
            'origin_longitude' => 47.9774,
            'destination_latitude' => 29.3759,
            'destination_longitude' => 48.0774,
        ],
    ];

    $results = $this->service->batchCalculate($pairs);

    expect($results)->toBeArray()->toHaveCount(2);

    foreach ($results as $result) {
        expect($result)->toHaveKeys(['distance_meters', 'estimated_arrival_seconds'])
            ->and($result['distance_meters'])->not->toBeNull()
            ->and($result['estimated_arrival_seconds'])->not->toBeNull();
    }
});
