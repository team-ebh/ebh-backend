<?php

declare(strict_types=1);

use App\Services\Cache\BaseCache;
use Illuminate\Support\Facades\Cache;

// Test cache class
class TestCache extends BaseCache
{
    protected function scope(): string
    {
        return 'test';
    }

    protected function ttl(): int
    {
        return 60;
    }
}

beforeEach(function () {
    Cache::flush();
});

describe('BaseCache', function () {
    describe('Context Handling', function () {
        test('generates correct key with customer context', function () {
            $cache = (new TestCache)->for('customer');
            $callCount = 0;

            $cache->get(123, function () use (&$callCount) {
                $callCount++;

                return 'value';
            });

            // Check that cache was created with correct key
            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeTrue();
        });

        test('generates correct key with rider context', function () {
            $cache = (new TestCache)->for('rider');
            $cache->get(456, fn () => 'value');

            expect(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeTrue();
        });

        test('generates correct key without context', function () {
            $cache = new TestCache;
            $cache->get(789, fn () => 'value');

            expect(Cache::tags(['test'])->has('test:789'))->toBeTrue();
        });
    });

    describe('Caching Behavior', function () {
        test('caches value and reuses it', function () {
            $callCount = 0;
            $callback = function () use (&$callCount) {
                $callCount++;

                return 'result';
            };

            $cache = (new TestCache)->for('customer');

            $result1 = $cache->get(123, $callback);
            $result2 = $cache->get(123, $callback);

            expect($result1)->toBe('result')
                ->and($result2)->toBe('result')
                ->and($callCount)->toBe(1);
        });

        test('different contexts have separate caches', function () {
            $callCount = 0;
            $callback = function () use (&$callCount) {
                $callCount++;

                return 'result';
            };

            TestCache::customer(123, $callback);
            TestCache::rider(123, $callback);

            expect($callCount)->toBe(2); // Called twice (different contexts)
        });
    });

    describe('Static Helpers', function () {
        test('customer helper works correctly', function () {
            $result = TestCache::customer(123, fn () => 'customer_value');

            expect($result)->toBe('customer_value')
                ->and(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeTrue();
        });

        test('rider helper works correctly', function () {
            $result = TestCache::rider(456, fn () => 'rider_value');

            expect($result)->toBe('rider_value')
                ->and(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeTrue();
        });
    });

    describe('Forgetting Cache', function () {
        test('forgetCustomer clears customer cache', function () {
            TestCache::customer(123, fn () => 'value');
            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeTrue();

            TestCache::forgetCustomer(123);
            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeFalse();
        });

        test('forgetRider clears rider cache', function () {
            TestCache::rider(456, fn () => 'value');
            expect(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeTrue();

            TestCache::forgetRider(456);
            expect(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeFalse();
        });

        test('forgetBoth clears both caches', function () {
            TestCache::customer(123, fn () => 'customer_value');
            TestCache::rider(456, fn () => 'rider_value');

            TestCache::forgetBoth(123, 456);

            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeFalse();
            expect(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeFalse();
        });

        test('forgetBoth handles null values gracefully', function () {
            TestCache::customer(123, fn () => 'value');

            TestCache::forgetBoth(123, null);
            TestCache::forgetBoth(null, 456);
            TestCache::forgetBoth(null, null);

            expect(true)->toBeTrue(); // No errors thrown
        });
    });

    describe('Flush', function () {
        test('flush clears all caches for scope', function () {
            TestCache::customer(123, fn () => 'value1');
            TestCache::rider(456, fn () => 'value2');

            TestCache::flush();

            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeFalse()
                ->and(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeFalse();
        });
    });

    describe('Tags', function () {
        test('can flush all customer caches across scopes', function () {
            TestCache::customer(123, fn () => 'value1');
            TestCache::customer(456, fn () => 'value2');

            Cache::tags(['customer'])->flush();

            expect(Cache::tags(['test', 'customer'])->has('test:customer:123'))->toBeFalse()
                ->and(Cache::tags(['test', 'customer'])->has('test:customer:456'))->toBeFalse();
        });

        test('can flush all rider caches across scopes', function () {
            TestCache::rider(123, fn () => 'value1');
            TestCache::rider(456, fn () => 'value2');

            Cache::tags(['rider'])->flush();

            expect(Cache::tags(['test', 'rider'])->has('test:rider:123'))->toBeFalse()
                ->and(Cache::tags(['test', 'rider'])->has('test:rider:456'))->toBeFalse();
        });
    });
});
