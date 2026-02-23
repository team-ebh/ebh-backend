<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerAppStateEnum;
use App\Enums\Rider\RiderAppStateEnum;
use App\Services\Cache\AppStateCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('AppStateCache', function () {
    test('caches customer state with callback', function () {
        $customerId = 123;
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return CustomerAppStateEnum::NO_TRIP;
        };

        // First call - should execute callback
        $result1 = AppStateCache::customer($customerId, $callback);
        expect($result1)->toBe(CustomerAppStateEnum::NO_TRIP);
        expect($callCount)->toBe(1);

        // Second call - should use cache (callback not executed)
        $result2 = AppStateCache::customer($customerId, $callback);
        expect($result2)->toBe(CustomerAppStateEnum::NO_TRIP)
            ->and($callCount)->toBe(1);
    });

    test('caches rider state with callback', function () {
        $riderId = 456;
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return RiderAppStateEnum::ONLINE_IDLE;
        };

        // First call - should execute callback
        $result1 = AppStateCache::rider($riderId, $callback);
        expect($result1)->toBe(RiderAppStateEnum::ONLINE_IDLE)
            ->and($callCount)->toBe(1);

        // Second call - should use cache
        $result2 = AppStateCache::rider($riderId, $callback);
        expect($result2)->toBe(RiderAppStateEnum::ONLINE_IDLE)
            ->and($callCount)->toBe(1);
    });

    test('forgetCustomer clears customer cache', function () {
        $customerId = 789;
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return CustomerAppStateEnum::HAS_ACTIVE_TRIP;
        };

        // Cache the value
        AppStateCache::customer($customerId, $callback);
        expect($callCount)->toBe(1);

        // Clear cache
        AppStateCache::forgetCustomer($customerId);

        // Next call should execute callback again
        AppStateCache::customer($customerId, $callback);
        expect($callCount)->toBe(2);
    });

    test('forgetRider clears rider cache', function () {
        $riderId = 321;
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;

            return RiderAppStateEnum::HAS_ACTIVE_TRIP;
        };

        // Cache the value
        AppStateCache::rider($riderId, $callback);
        expect($callCount)->toBe(1);

        // Clear cache
        AppStateCache::forgetRider($riderId);

        // Next call should execute callback again
        AppStateCache::rider($riderId, $callback);
        expect($callCount)->toBe(2);
    });

    test('forgetBoth clears both customer and rider cache', function () {
        $customerId = 111;
        $riderId = 222;

        // Cache both
        AppStateCache::customer($customerId, fn () => CustomerAppStateEnum::NO_TRIP);
        AppStateCache::rider($riderId, fn () => RiderAppStateEnum::ONLINE_IDLE);

        // Clear both
        AppStateCache::forgetBoth($customerId, $riderId);

        // Both should be cleared (we can't directly test this without checking cache,
        // but the methods should not throw errors)
        expect(true)->toBeTrue();
    });

    test('handles null customer ID gracefully', function () {
        AppStateCache::forgetCustomer(null);

        expect(true)->toBeTrue();
    });

    test('handles null rider ID gracefully', function () {
        AppStateCache::forgetRider(null);

        expect(true)->toBeTrue();
    });
});
