<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Rider;
use App\Models\Trip;
use App\Services\Cache\TripCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('TripCache', function () {
    describe('Basic Cache Operations', function () {
        test('caches customer trip with callback', function () {
            $customerId = 123;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 456, 'status' => 'active'];
            };

            // First call - should execute callback
            $result1 = TripCache::customer($customerId, $callback);
            expect($result1)->toBe(['trip_id' => 456, 'status' => 'active']);
            expect($callCount)->toBe(1);

            // Second call - should use cache (callback not executed)
            $result2 = TripCache::customer($customerId, $callback);
            expect($result2)->toBe(['trip_id' => 456, 'status' => 'active'])
                ->and($callCount)->toBe(1);
        });

        test('caches rider trip with callback', function () {
            $riderId = 789;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 999, 'status' => 'on_trip'];
            };

            // First call - should execute callback
            $result1 = TripCache::rider($riderId, $callback);
            expect($result1)->toBe(['trip_id' => 999, 'status' => 'on_trip'])
                ->and($callCount)->toBe(1);

            // Second call - should use cache
            $result2 = TripCache::rider($riderId, $callback);
            expect($result2)->toBe(['trip_id' => 999, 'status' => 'on_trip'])
                ->and($callCount)->toBe(1);
        });

        test('customer and rider caches are separate', function () {
            $customerId = 100;
            $riderId = 100; // Same ID but different context

            $customerCallback = fn () => ['type' => 'customer_trip'];
            $riderCallback = fn () => ['type' => 'rider_trip'];

            // Cache both
            $customerResult = TripCache::customer($customerId, $customerCallback);
            $riderResult = TripCache::rider($riderId, $riderCallback);

            // They should have different values
            expect($customerResult)->toBe(['type' => 'customer_trip'])
                ->and($riderResult)->toBe(['type' => 'rider_trip']);
        });

        test('returns null when callback returns null', function () {
            $customerId = 200;

            $result = TripCache::customer($customerId, fn () => null);

            expect($result)->toBeNull();
        });
    });

    describe('Cache Invalidation', function () {
        test('forgetCustomer clears customer cache', function () {
            $customerId = 300;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 301];
            };

            // Cache the value
            TripCache::customer($customerId, $callback);
            expect($callCount)->toBe(1);

            // Clear cache
            TripCache::forgetCustomer($customerId);

            // Next call should execute callback again
            TripCache::customer($customerId, $callback);
            expect($callCount)->toBe(2);
        });

        test('forgetRider clears rider cache', function () {
            $riderId = 400;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 401];
            };

            // Cache the value
            TripCache::rider($riderId, $callback);
            expect($callCount)->toBe(1);

            // Clear cache
            TripCache::forgetRider($riderId);

            // Next call should execute callback again
            TripCache::rider($riderId, $callback);
            expect($callCount)->toBe(2);
        });

        test('forgetBoth clears both customer and rider cache', function () {
            $customerId = 500;
            $riderId = 600;
            $customerCallCount = 0;
            $riderCallCount = 0;

            $customerCallback = function () use (&$customerCallCount) {
                $customerCallCount++;

                return ['type' => 'customer'];
            };

            $riderCallback = function () use (&$riderCallCount) {
                $riderCallCount++;

                return ['type' => 'rider'];
            };

            // Cache both
            TripCache::customer($customerId, $customerCallback);
            TripCache::rider($riderId, $riderCallback);
            expect($customerCallCount)->toBe(1)
                ->and($riderCallCount)->toBe(1);

            // Clear both
            TripCache::forgetBoth($customerId, $riderId);

            // Both should execute callbacks again
            TripCache::customer($customerId, $customerCallback);
            TripCache::rider($riderId, $riderCallback);
            expect($customerCallCount)->toBe(2)
                ->and($riderCallCount)->toBe(2);
        });

        test('handles null customer ID gracefully', function () {
            TripCache::forgetCustomer(null);

            expect(true)->toBeTrue();
        });

        test('handles null rider ID gracefully', function () {
            TripCache::forgetRider(null);

            expect(true)->toBeTrue();
        });

        test('forgetBoth handles null values gracefully', function () {
            TripCache::forgetBoth(null, null);
            TripCache::forgetBoth(123, null);
            TripCache::forgetBoth(null, 456);

            expect(true)->toBeTrue();
        });
    });

    describe('Integration with Real Actions', function () {
        test('caches trip data and reuses it on subsequent calls', function () {
            $customerId = 700;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 701, 'status' => 'active'];
            };

            // First call - should execute callback
            $result1 = TripCache::customer($customerId, $callback);
            expect($callCount)->toBe(1);

            // Second call - should use cache
            $result2 = TripCache::customer($customerId, $callback);
            expect($callCount)->toBe(1) // Still 1, not 2
                ->and($result2)->toBe($result1);
        });

        test('trip modification actions clear cache', function () {
            $customerId = 800;
            $riderId = 900;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['trip_id' => 1000];
            };

            // Cache trip data
            TripCache::customer($customerId, $callback);
            TripCache::rider($riderId, $callback);
            expect($callCount)->toBe(2);

            // Simulate trip modification (like AcceptTripRequestAction)
            TripCache::forgetBoth($customerId, $riderId);

            // Next calls should execute callback again
            TripCache::customer($customerId, $callback);
            TripCache::rider($riderId, $callback);
            expect($callCount)->toBe(4); // 2 + 2 = 4
        });
    });

    describe('Cache TTL and Expiration', function () {
        test('cache reuses cached value when called multiple times', function () {
            $customerId = 1000;
            $callCount = 0;

            $callback = function () use (&$callCount) {
                $callCount++;

                return ['cached' => $callCount];
            };

            // Cache a value
            $result1 = TripCache::customer($customerId, $callback);
            expect($result1)->toBe(['cached' => 1])
                ->and($callCount)->toBe(1);

            // The value should be cached
            $result2 = TripCache::customer($customerId, $callback);
            expect($result2)->toBe(['cached' => 1]) // Should return cached value, not 2
                ->and($callCount)->toBe(1); // Callback not executed again
        });
    });

    describe('Multiple Customers/Riders', function () {
        test('caches are isolated per customer', function () {
            $customer1 = 1001;
            $customer2 = 1002;

            $result1 = TripCache::customer($customer1, fn () => ['customer' => $customer1]);
            $result2 = TripCache::customer($customer2, fn () => ['customer' => $customer2]);

            expect($result1)->toBe(['customer' => $customer1])
                ->and($result2)->toBe(['customer' => $customer2]);
        });

        test('caches are isolated per rider', function () {
            $rider1 = 2001;
            $rider2 = 2002;

            $result1 = TripCache::rider($rider1, fn () => ['rider' => $rider1]);
            $result2 = TripCache::rider($rider2, fn () => ['rider' => $rider2]);

            expect($result1)->toBe(['rider' => $rider1])
                ->and($result2)->toBe(['rider' => $rider2]);
        });

        test('clearing one customer cache does not affect other customers', function () {
            $customer1 = 3001;
            $customer2 = 3002;
            $callCount1 = 0;
            $callCount2 = 0;

            // Cache both
            TripCache::customer($customer1, function () use (&$callCount1) {
                $callCount1++;

                return ['customer' => 1];
            });
            TripCache::customer($customer2, function () use (&$callCount2) {
                $callCount2++;

                return ['customer' => 2];
            });

            expect($callCount1)->toBe(1)
                ->and($callCount2)->toBe(1);

            // Clear only customer1
            TripCache::forgetCustomer($customer1);

            // Customer1 should reload, customer2 should use cache
            TripCache::customer($customer1, function () use (&$callCount1) {
                $callCount1++;

                return ['customer' => 1];
            });
            TripCache::customer($customer2, function () use (&$callCount2) {
                $callCount2++;

                return ['customer' => 2];
            });

            expect($callCount1)->toBe(2) // Reloaded
                ->and($callCount2)->toBe(1); // Still cached
        });
    });
});
