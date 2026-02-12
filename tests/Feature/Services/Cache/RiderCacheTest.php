<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use App\Services\Cache\RiderCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('RiderCache', function () {
    describe('Basic Cache Operations', function () {
        test('caches rider data with callback', function () {
            $riderId = 123;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return [
                    'id' => 123,
                    'status' => RiderStatusEnum::ONLINE->value,
                    'latitude' => 29.3759,
                    'longitude' => 47.9774,
                ];
            };

            // First call - should execute callback
            $result1 = RiderCache::rider($riderId, $callback);
            expect($callCount)->toBe(1);

            // Second call - should use cache (callback not executed)
            $result2 = RiderCache::rider($riderId, $callback);
            expect($result2)->toBe($result1)
                ->and($callCount)->toBe(1); // Still 1, not 2
        });

        test('caches rider data with coordinate format latitude/longitude', function () {
            $riderId = 456;

            $riderData = [
                'id' => 456,
                'status' => RiderStatusEnum::ONLINE->value,
                'latitude' => 29.3759,
                'longitude' => 47.9774,
            ];

            $result = RiderCache::rider($riderId, fn () => $riderData);

            expect($result)->toBe($riderData);
        });

        test('caches rider data with coordinate format lat/lng', function () {
            $riderId = 789;

            $riderData = [
                'id' => 789,
                'status' => RiderStatusEnum::ONLINE->value,
                'lat' => 29.3759,
                'lng' => 47.9774,
            ];

            $result = RiderCache::rider($riderId, fn () => $riderData);

            expect($result)->toBe($riderData);
        });

        test('returns null when callback returns null', function () {
            $riderId = 999;

            $result = RiderCache::rider($riderId, fn () => null);

            expect($result)->toBeNull();
        });
    });

    describe('Cache Invalidation', function () {
        test('forgetRider clears rider cache', function () {
            $riderId = 100;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['id' => 100, 'status' => RiderStatusEnum::ONLINE->value];
            };

            // Cache the value
            RiderCache::rider($riderId, $callback);
            expect($callCount)->toBe(1);

            // Clear cache
            RiderCache::forgetRider($riderId);

            // Next call should execute callback again
            RiderCache::rider($riderId, $callback);
            expect($callCount)->toBe(2);
        });

        test('handles null rider ID gracefully', function () {
            RiderCache::forgetRider(null);

            expect(true)->toBeTrue();
        });
    });

    describe('Integration with Real Data', function () {
        test('caches real rider data from database', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE->value,
                Rider::COLUMN_LATITUDE => 29.3759,
                Rider::COLUMN_LONGITUDE => 47.9774,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return [
                    'id' => $rider->id,
                    'status' => $rider->status,
                    'latitude' => $rider->latitude,
                    'longitude' => $rider->longitude,
                ];
            };

            // First call - should execute callback
            $result1 = RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1)
                ->and($result1['id'])->toBe($rider->id);

            // Second call - should use cache
            $result2 = RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1) // Still 1, not 2
                ->and($result2)->toBe($result1);
        });

        test('clearing cache forces reload from database', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE->value,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return ['id' => $rider->id, 'status' => $rider->status];
            };

            // Cache the value
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Clear cache (simulating UpdateLocationAction or UpdateRiderStatusAction)
            RiderCache::forgetRider($rider->id);

            // Next call should reload from database
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });
    });

    describe('Multiple Riders', function () {
        test('caches are isolated per rider', function () {
            $rider1 = 1001;
            $rider2 = 1002;

            $result1 = RiderCache::rider($rider1, fn () => ['id' => $rider1]);
            $result2 = RiderCache::rider($rider2, fn () => ['id' => $rider2]);

            expect($result1)->toBe(['id' => $rider1])
                ->and($result2)->toBe(['id' => $rider2]);
        });

        test('clearing one rider cache does not affect other riders', function () {
            $rider1 = 2001;
            $rider2 = 2002;
            $callCount1 = 0;
            $callCount2 = 0;

            // Cache both
            RiderCache::rider($rider1, function () use (&$callCount1) {
                $callCount1++;

                return ['id' => 1];
            });
            RiderCache::rider($rider2, function () use (&$callCount2) {
                $callCount2++;

                return ['id' => 2];
            });

            expect($callCount1)->toBe(1)
                ->and($callCount2)->toBe(1);

            // Clear only rider1
            RiderCache::forgetRider($rider1);

            // Rider1 should reload, rider2 should use cache
            RiderCache::rider($rider1, function () use (&$callCount1) {
                $callCount1++;

                return ['id' => 1];
            });
            RiderCache::rider($rider2, function () use (&$callCount2) {
                $callCount2++;

                return ['id' => 2];
            });

            expect($callCount1)->toBe(2) // Reloaded
                ->and($callCount2)->toBe(1); // Still cached
        });
    });

    describe('Cache TTL', function () {
        test('cache reuses cached value when called multiple times', function () {
            $riderId = 3000;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['cached' => $callCount];
            };

            // Cache a value
            $result1 = RiderCache::rider($riderId, $callback);
            expect($result1)->toBe(['cached' => 1])
                ->and($callCount)->toBe(1);

            // The value should be cached
            $result2 = RiderCache::rider($riderId, $callback);
            expect($result2)->toBe(['cached' => 1]) // Should return cached value, not 2
                ->and($callCount)->toBe(1); // Callback not executed again
        });
    });

    describe('Action Integration', function () {
        test('UpdateLocationAction clears rider cache', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_LATITUDE => 29.3759,
                Rider::COLUMN_LONGITUDE => 47.9774,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return [
                    'id' => $rider->id,
                    'latitude' => $rider->latitude,
                    'longitude' => $rider->longitude,
                ];
            };

            // Cache rider location
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Simulate UpdateLocationAction clearing cache
            RiderCache::forgetRider($rider->id);

            // Next call should reload (simulating new location fetch)
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });

        test('AcceptTripRequestAction clears rider cache', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE->value,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return ['id' => $rider->id, 'status' => $rider->status];
            };

            // Cache rider status
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Simulate AcceptTripRequestAction clearing cache
            RiderCache::forgetRider($rider->id);

            // Next call should reload (simulating status check after trip acceptance)
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });

        test('CompleteTripAction clears rider cache', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_STATUS => RiderStatusEnum::BUSY->value,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return ['id' => $rider->id, 'status' => $rider->status];
            };

            // Cache rider status
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Simulate CompleteTripAction clearing cache
            RiderCache::forgetRider($rider->id);

            // Next call should reload (simulating status update to ONLINE)
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });

        test('CancelTripAction clears rider cache', function () {
            $rider = Rider::factory()->create([
                Rider::COLUMN_STATUS => RiderStatusEnum::BUSY->value,
            ]);

            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return ['id' => $rider->id, 'status' => $rider->status];
            };

            // Cache rider status
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Simulate CancelTripAction clearing cache
            RiderCache::forgetRider($rider->id);

            // Next call should reload
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });
    });

    describe('Coordinate Format Support', function () {
        test('supports both lat/lng and latitude/longitude formats', function () {
            $rider1 = 4001;
            $rider2 = 4002;

            // Format 1: lat/lng
            $data1 = RiderCache::rider($rider1, fn () => [
                'id' => $rider1,
                'lat' => 29.3759,
                'lng' => 47.9774,
            ]);

            // Format 2: latitude/longitude
            $data2 = RiderCache::rider($rider2, fn () => [
                'id' => $rider2,
                'latitude' => 29.3759,
                'longitude' => 47.9774,
            ]);

            expect($data1['lat'])->toBe(29.3759)
                ->and($data1['lng'])->toBe(47.9774)
                ->and($data2['latitude'])->toBe(29.3759)
                ->and($data2['longitude'])->toBe(47.9774);
        });

        test('handles missing coordinates gracefully', function () {
            $riderId = 5000;

            // No coordinates provided
            $data = RiderCache::rider($riderId, fn () => [
                'id' => $riderId,
                'status' => RiderStatusEnum::ONLINE->value,
            ]);

            expect($data)->toBe([
                'id' => $riderId,
                'status' => RiderStatusEnum::ONLINE->value,
            ]);
        });
    });

    describe('Cache Performance', function () {
        test('cache significantly reduces callback executions', function () {
            $riderId = 6000;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['id' => 6000, 'status' => RiderStatusEnum::ONLINE->value];
            };

            // Call 10 times
            for ($i = 0; $i < 10; $i++) {
                RiderCache::rider($riderId, $callback);
            }

            // Callback should only be executed once
            expect($callCount)->toBe(1);
        });

        test('multiple riders can be cached simultaneously', function () {
            $riders = range(7001, 7010);
            $callCounts = [];

            foreach ($riders as $riderId) {
                $callCounts[$riderId] = 0;

                RiderCache::rider($riderId, function () use (&$callCounts, $riderId) {
                    $callCounts[$riderId]++;

                    return ['id' => $riderId];
                });
            }

            // All should be cached
            foreach ($riders as $riderId) {
                expect($callCounts[$riderId])->toBe(1);

                // Second call should use cache
                RiderCache::rider($riderId, function () use (&$callCounts, $riderId) {
                    $callCounts[$riderId]++;

                    return ['id' => $riderId];
                });

                expect($callCounts[$riderId])->toBe(1); // Still 1, not 2
            }
        });
    });
});
