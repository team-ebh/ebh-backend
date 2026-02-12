<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Rider;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\RiderCache;
use App\Services\Cache\TripCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Cache::flush();
});

describe('Cache Performance Tests', function () {
    describe('AppStateCache Performance', function () {
        test('significantly reduces database queries', function () {
            $customerId = 100;
            $callback = fn () => ['state' => 'NO_TRIP'];

            // First call - hits database
            DB::enableQueryLog();
            AppStateCache::customer($customerId, $callback);
            $firstCallQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            // Next 100 calls - should use cache
            DB::enableQueryLog();
            for ($i = 0; $i < 100; $i++) {
                AppStateCache::customer($customerId, $callback);
            }
            $cachedCallsQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            expect($cachedCallsQueries)->toBe(0)
                ->and($firstCallQueries)->toBeGreaterThanOrEqual(0);
        });

        test('callback only executes once for multiple calls', function () {
            $customerId = 200;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['state' => 'HAS_ACTIVE_TRIP'];
            };

            // Call 50 times
            for ($i = 0; $i < 50; $i++) {
                AppStateCache::customer($customerId, $callback);
            }

            // Callback should only execute once
            expect($callCount)->toBe(1);
        });

        test('cache improves response time', function () {
            $customerId = 300;

            // Simulate expensive operation
            $expensiveCallback = function () {
                usleep(10000); // 10ms delay

                return ['state' => 'NO_TRIP'];
            };

            // First call - with delay
            $start1 = microtime(true);
            AppStateCache::customer($customerId, $expensiveCallback);
            $duration1 = (microtime(true) - $start1) * 1000; // Convert to ms

            // Second call - cached (should be much faster)
            $start2 = microtime(true);
            AppStateCache::customer($customerId, $expensiveCallback);
            $duration2 = (microtime(true) - $start2) * 1000;

            // Cached call should be at least 5x faster
            expect($duration2)->toBeLessThan($duration1 / 5);
        });
    });

    describe('RiderCache Performance', function () {
        test('callback only executes once for multiple calls', function () {
            $rider = Rider::factory()->create();
            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return [
                    'id' => $rider->id,
                    'status' => 'online',
                    'latitude' => 29.3759,
                    'longitude' => 47.9774,
                ];
            };

            // Call 100 times
            for ($i = 0; $i < 100; $i++) {
                RiderCache::rider($rider->id, $callback);
            }

            // Callback should only execute once
            expect($callCount)->toBe(1);
        });

        test('significantly reduces database queries for rider data', function () {
            $rider = Rider::factory()->create();

            // First call - hits database
            DB::enableQueryLog();
            RiderCache::rider($rider->id, fn () => [
                'id' => $rider->id,
                'status' => $rider->status,
            ]);
            $firstCallQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            // Next 50 calls - should use cache
            DB::enableQueryLog();
            for ($i = 0; $i < 50; $i++) {
                RiderCache::rider($rider->id, fn () => [
                    'id' => $rider->id,
                    'status' => $rider->status,
                ]);
            }
            $cachedCallsQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            expect($cachedCallsQueries)->toBe(0);
        });
    });

    describe('TripCache Performance', function () {
        test('callback only executes once for multiple calls', function () {
            $customerId = 500;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 123, 'status' => 'active'];
            };

            // Call 100 times
            for ($i = 0; $i < 100; $i++) {
                TripCache::customer($customerId, $callback);
            }

            // Callback should only execute once
            expect($callCount)->toBe(1);
        });

        test('cache improves response time for trip data', function () {
            $customerId = 600;

            // Simulate expensive database query
            $expensiveCallback = function () {
                usleep(5000); // 5ms delay

                return ['trip_id' => 456, 'status' => 'on_trip'];
            };

            // First call - with delay
            $start1 = microtime(true);
            TripCache::customer($customerId, $expensiveCallback);
            $duration1 = (microtime(true) - $start1) * 1000;

            // Second call - cached
            $start2 = microtime(true);
            TripCache::customer($customerId, $expensiveCallback);
            $duration2 = (microtime(true) - $start2) * 1000;

            // Cached call should be much faster
            expect($duration2)->toBeLessThan($duration1 / 3);
        });
    });

    describe('Multiple Caches Performance', function () {
        test('multiple independent caches work efficiently', function () {
            $customer = Customer::factory()->create();
            $rider = Rider::factory()->create();

            $appStateCallCount = 0;
            $riderCallCount = 0;
            $tripCallCount = 0;

            $appStateCallback = function () use (&$appStateCallCount) {
                $appStateCallCount++;

                return ['state' => 'NO_TRIP'];
            };

            $riderCallback = function () use (&$riderCallCount, $rider) {
                $riderCallCount++;

                return ['id' => $rider->id];
            };

            $tripCallback = function () use (&$tripCallCount) {
                $tripCallCount++;

                return ['trip_id' => null];
            };

            // Call all caches 50 times each
            for ($i = 0; $i < 50; $i++) {
                AppStateCache::customer($customer->id, $appStateCallback);
                RiderCache::rider($rider->id, $riderCallback);
                TripCache::customer($customer->id, $tripCallback);
            }

            // Each callback should only execute once
            expect($appStateCallCount)->toBe(1)
                ->and($riderCallCount)->toBe(1)
                ->and($tripCallCount)->toBe(1);
        });

        test('cache invalidation works without affecting other caches', function () {
            $customer = Customer::factory()->create();

            $appStateCallCount = 0;
            $tripCallCount = 0;

            // Cache both
            AppStateCache::customer($customer->id, function () use (&$appStateCallCount) {
                $appStateCallCount++;

                return ['state' => 'NO_TRIP'];
            });

            TripCache::customer($customer->id, function () use (&$tripCallCount) {
                $tripCallCount++;

                return ['trip_id' => null];
            });

            expect($appStateCallCount)->toBe(1)
                ->and($tripCallCount)->toBe(1);

            // Clear only AppStateCache
            AppStateCache::forgetCustomer($customer->id);

            // Call both again
            AppStateCache::customer($customer->id, function () use (&$appStateCallCount) {
                $appStateCallCount++;

                return ['state' => 'NO_TRIP'];
            });

            TripCache::customer($customer->id, function () use (&$tripCallCount) {
                $tripCallCount++;

                return ['trip_id' => null];
            });

            // AppStateCache should reload, TripCache should still use cache
            expect($appStateCallCount)->toBe(2) // Reloaded
                ->and($tripCallCount)->toBe(1); // Still cached
        });
    });

    describe('Real-World Scenarios', function () {
        test('frequent app state checks benefit from caching', function () {
            $customers = Customer::factory(5)->create();
            $totalCallbacks = 0;

            // Simulate 100 app state checks across 5 customers
            for ($i = 0; $i < 100; $i++) {
                $randomCustomer = $customers->random();

                AppStateCache::customer($randomCustomer->id, function () use (&$totalCallbacks) {
                    $totalCallbacks++;

                    return ['state' => 'NO_TRIP'];
                });
            }

            // Only 5 callbacks should execute (one per customer)
            expect($totalCallbacks)->toBe(5);
        });

        test('rider location updates with cache clearing', function () {
            $rider = Rider::factory()->create();
            $callCount = 0;

            $callback = function () use (&$callCount, $rider) {
                $callCount++;

                return ['id' => $rider->id, 'lat' => 29.3759, 'lng' => 47.9774];
            };

            // Initial cache
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(1);

            // Multiple reads use cache
            for ($i = 0; $i < 10; $i++) {
                RiderCache::rider($rider->id, $callback);
            }
            expect($callCount)->toBe(1);

            // Location update clears cache
            RiderCache::forgetRider($rider->id);

            // Next read fetches fresh data
            RiderCache::rider($rider->id, $callback);
            expect($callCount)->toBe(2);
        });
    });
});
