<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DistanceCalculationService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DistanceCalculationServiceTest extends TestCase
{
    private DistanceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DistanceCalculationService;
    }

    public function test_calculates_distance_between_two_points_using_haversine(): void
    {
        // Temporarily disable API key to force Haversine calculation
        Config::set('services.google_maps.api_key', '');

        // Kuwait City coordinates (approximately)
        $originLat = 29.3759;
        $originLng = 47.9774;

        // Coordinates approximately 5km away
        $destLat = 29.4159;
        $destLng = 47.9774;

        $result = $this->service->calculateDistanceAndDuration(
            $originLat,
            $originLng,
            $destLat,
            $destLng
        );

        // Assert distance is calculated
        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance_meters', $result);
        $this->assertArrayHasKey('estimated_arrival_minutes', $result);

        // Distance should be approximately 4500 meters
        $this->assertNotNull($result['distance_meters']);
        $this->assertGreaterThan(4000, $result['distance_meters']);
        $this->assertLessThan(5000, $result['distance_meters']);

        // Estimated arrival should be calculated
        $this->assertNotNull($result['estimated_arrival_minutes']);
        $this->assertGreaterThan(0, $result['estimated_arrival_minutes']);
    }

    public function test_calculates_zero_distance_for_same_location(): void
    {
        Config::set('services.google_maps.api_key', '');

        $lat = 29.3759;
        $lng = 47.9774;

        $result = $this->service->calculateDistanceAndDuration($lat, $lng, $lat, $lng);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['distance_meters']);
        $this->assertEquals(0, $result['estimated_arrival_minutes']);
    }

    public function test_handles_large_distances(): void
    {
        Config::set('services.google_maps.api_key', '');

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

        $this->assertIsArray($result);
        $this->assertNotNull($result['distance_meters']);

        // Distance should be approximately 800-860km
        $this->assertGreaterThan(750000, $result['distance_meters']);
        $this->assertLessThan(900000, $result['distance_meters']);

        // Estimated time should be calculated
        $this->assertGreaterThan(0, $result['estimated_arrival_minutes']);
    }

    public function test_batch_calculate_multiple_pairs(): void
    {
        Config::set('services.google_maps.api_key', '');

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

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertArrayHasKey('distance_meters', $result);
            $this->assertArrayHasKey('estimated_arrival_minutes', $result);
            $this->assertNotNull($result['distance_meters']);
            $this->assertNotNull($result['estimated_arrival_minutes']);
        }
    }
}
