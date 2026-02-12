# Cache Service Examples

## 1️⃣ Shared Cache (Used by Both Customer and Rider)

### TripCache
```php
<?php

namespace App\Services\Cache;

/**
 * Trip Cache Service
 *
 * Used by both customer and rider to cache trip data
 *
 * Keys:
 * - trip:customer:123 (customer's trips)
 * - trip:rider:456 (rider's trips)
 */
class TripCache extends BaseCache
{
    protected function scope(): string
    {
        return 'trip';
    }
}

// Usage:
TripCache::customer($customerId, fn() => $this->customerTripRepository->getActiveTrip($customerId));
TripCache::rider($riderId, fn() => $this->riderTripRepository->getActiveTrip($riderId));

TripCache::forgetCustomer($customerId);
TripCache::forgetRider($riderId);
TripCache::forgetBoth($customerId, $riderId);
```

## 2️⃣ Customer-Only Cache

### CustomerProfileCache
```php
<?php

namespace App\Services\Cache;

/**
 * Customer Profile Cache Service
 *
 * Only used by customers to cache profile data and preferences
 *
 * Keys:
 * - customer_profile:customer:123
 */
class CustomerProfileCache extends BaseCache
{
    protected function scope(): string
    {
        return 'customer_profile';
    }
}

// Usage:
CustomerProfileCache::customer($customerId, fn() => $this->customerRepository->getProfileWithPreferences($customerId));
CustomerProfileCache::forgetCustomer($customerId);
```

## 3️⃣ Rider-Only Cache

### EarningsCache
```php
<?php

namespace App\Services\Cache;

/**
 * Earnings Cache Service
 *
 * Only used by riders to cache earnings data
 *
 * Keys:
 * - earnings:rider:456
 */
class EarningsCache extends BaseCache
{
    protected function scope(): string
    {
        return 'earnings';
    }
}

// Usage:
EarningsCache::rider($riderId, fn() => $this->earningsRepository->calculateMonthlyEarnings($riderId));
EarningsCache::forgetRider($riderId);
```

## 4️⃣ Global Cache (No Context)

### VehicleSettingsCache
```php
<?php

namespace App\Services\Cache;

/**
 * Vehicle Settings Cache Service
 *
 * Global cache for vehicle settings (car types, colors, makes, models)
 * No context needed as these are shared across all users
 *
 * Keys:
 * - vehicle_settings:car_types
 * - vehicle_settings:car_colors
 * - vehicle_settings:car_makes
 */
class VehicleSettingsCache extends BaseCache
{
    protected function scope(): string
    {
        return 'vehicle_settings';
    }
}

// Usage:
(new VehicleSettingsCache)->get('car_types', fn() => VehicleSetting::getByType('car_types'));
(new VehicleSettingsCache)->get('car_colors', fn() => VehicleSetting::getByType('car_colors'));
(new VehicleSettingsCache)->forget('car_types');

// Or flush all vehicle settings:
VehicleSettingsCache::flush(); // Clear all vehicle settings
```

## 5️⃣ Custom Context Cache

### NotificationCache
```php
<?php

namespace App\Services\Cache;

/**
 * Notification Cache Service
 *
 * Can be used with any context: customer, rider, admin
 *
 * Keys:
 * - notification:customer:123
 * - notification:rider:456
 * - notification:admin:789
 */
class NotificationCache extends BaseCache
{
    protected function scope(): string
    {
        return 'notification';
    }

    // Custom helper for admin
    public static function admin($id, callable $callback)
    {
        return (new static)->for('admin')->get($id, $callback);
    }

    public static function forgetAdmin($id): void
    {
        (new static)->for('admin')->forget($id);
    }
}

// Usage:
NotificationCache::customer($customerId, fn() => $this->notificationRepository->getUnread($customerId));
NotificationCache::rider($riderId, fn() => $this->notificationRepository->getUnread($riderId));
NotificationCache::admin($adminId, fn() => $this->notificationRepository->getUnread($adminId));

// Or dynamic:
(new NotificationCache)->for('support')->get($supportId, fn() => ...);
```

## 6️⃣ Configuration in cache.php

```php
'scopes' => [
    'app_state' => ['enabled' => true, 'ttl' => 1800],           // 30 min (customer/rider app state)
    'trip' => ['enabled' => true, 'ttl' => 900],                 // 15 min (active trip data)
    'customer_profile' => ['enabled' => true, 'ttl' => 3600],    // 1 hour (customer profiles)
    'earnings' => ['enabled' => true, 'ttl' => 1800],            // 30 min (rider earnings)
    'vehicle_settings' => ['enabled' => true, 'ttl' => 86400],   // 24 hours (vehicle settings - rarely change)
    'notification' => ['enabled' => true, 'ttl' => 600],         // 10 min (notifications)
],
```

## 7️⃣ Disabling Cache for Testing

```php
// .env
APP_STATE_CACHE_ENABLED=false

// Or in test:
config(['cache.scopes.app_state.enabled' => false]);
config(['cache.scopes.trip.enabled' => false]);
```

## 8️⃣ Flushing with Cache Tags

```php
// Clear all app_state caches (both customer and rider)
AppStateCache::flush();

// Clear all customer caches across all scopes
Cache::tags(['customer'])->flush();

// Clear all rider caches across all scopes
Cache::tags(['rider'])->flush();

// Clear specific scope for specific context
Cache::tags(['trip', 'customer'])->flush();
Cache::tags(['trip', 'rider'])->flush();

// Clear all vehicle settings
VehicleSettingsCache::flush();
```

## 9️⃣ Complete Usage Examples

### In GetAppStateAction

```php
<?php

namespace App\Actions\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\AppStateDTO;
use App\Enums\Customer\CustomerAppStateEnum;
use App\Services\Cache\AppStateCache;

class GetAppStateAction
{
    public function __invoke(AppStateDTO $dto): CustomerAppStateEnum
    {
        return AppStateCache::customer(
            $dto->customerId,
            fn() => $this->calculateCustomerAppState($dto->customerId)
        );
    }

    private function calculateCustomerAppState(int $customerId): CustomerAppStateEnum
    {
        // Heavy computation here
        // Check active trips, scheduled trips, pending payments, etc.
        // This will only run if cache is empty
    }
}
```

### Clearing Cache After Trip Creation

```php
<?php

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\StoreTripDTO;
use App\Services\Cache\AppStateCache;

class StoreTripAction
{
    public function __invoke(TripStoreDTO $dto): array
    {
        $result = safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'storeTrip'], $dto);

        // Clear customer cache after trip creation (app state changes)
        AppStateCache::forgetCustomer($dto->customerId);

        return $result;
    }
}
```

### Clearing Cache for Both Customer and Rider

```php
<?php

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\Services\Cache\AppStateCache;

class AcceptTripRequestAction
{
    public function __invoke(AcceptTripRequestDTO $dto): array
    {
        $result = safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'acceptTrip'], $dto);

        // Clear cache for both rider and customer (both states change)
        AppStateCache::forgetBoth(
            $dto->tripRequest->trip->customer_id,
            $dto->riderId
        );

        return $result;
    }
}
```

### Caching Vehicle Settings Globally

```php
<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Services\Cache\VehicleSettingsCache;
use App\Models\VehicleSetting;

class VehicleSettingsController extends Controller
{
    public function getCarTypes()
    {
        $carTypes = (new VehicleSettingsCache)->get(
            'car_types',
            fn() => VehicleSetting::getByType(VehicleSetting::TYPE_CAR_TYPES)
        );

        return response()->json($carTypes);
    }

    public function getCarColors()
    {
        $carColors = (new VehicleSettingsCache)->get(
            'car_colors',
            fn() => VehicleSetting::getByType(VehicleSetting::TYPE_CAR_COLORS)
        );

        return response()->json($carColors);
    }
}
```

### Clearing Vehicle Settings Cache After Admin Update

```php
<?php

namespace App\Filament\Resources\VehicleSettingResource\Pages;

use App\Services\Cache\VehicleSettingsCache;
use Filament\Resources\Pages\EditRecord;

class EditVehicleSetting extends EditRecord
{
    protected function afterSave(): void
    {
        // Clear specific vehicle setting type from cache
        (new VehicleSettingsCache)->forget($this->record->type);

        // Or clear all vehicle settings if needed
        VehicleSettingsCache::flush();
    }
}
```

## 🔟 Key Benefits

1. **Simple to Create**: Just extend `BaseCache` and implement `scope()` method
2. **Context-Aware**: Automatically handles customer/rider/admin contexts
3. **Config-Driven**: Each scope has its own TTL and enabled/disabled setting
4. **Tag-Based**: Clear caches by scope, context, or both
5. **Testable**: Easy to disable for testing
6. **Consistent**: Same pattern across all cache services
7. **Type-Safe**: Full IDE autocomplete support
8. **Minimal Code**: Typically only 9 lines per cache service

## ⚠️ Best Practices

1. **Always use callbacks**: Never store data directly, always use callbacks
2. **Keep scope names short**: Use `trip`, not `trip_cache`
3. **Clear cache appropriately**: Clear only what's needed, not everything
4. **Configure TTL wisely**:
   - Short TTL (10-15 min) for frequently changing data (trips, app state)
   - Medium TTL (30-60 min) for moderate data (profiles, earnings)
   - Long TTL (24 hours) for static data (vehicle settings, app settings)
5. **Use tags for bulk operations**: Clear multiple related caches at once
6. **Test with cache disabled**: Ensure your code works without cache
7. **Handle null IDs gracefully**: The system already handles null IDs, no need to check
8. **Clear cache after updates**: Always clear relevant cache after data changes
9. **Use forgetBoth() when appropriate**: When an action affects both customer and rider (e.g., trip acceptance)

## 🎯 Real-World Scenarios

### Scenario 1: Customer Creates a Trip
```php
// In StoreTripAction
AppStateCache::forgetCustomer($customerId); // Customer now has active trip
```

### Scenario 2: Rider Accepts Trip
```php
// In AcceptTripRequestAction
AppStateCache::forgetBoth($customerId, $riderId); // Both states change
```

### Scenario 3: Trip Completed
```php
// In CompleteTripAction
AppStateCache::forgetBoth($customerId, $riderId); // Both states change
TripCache::forgetBoth($customerId, $riderId);     // Clear trip cache too
```

### Scenario 4: Admin Updates Vehicle Settings
```php
// In VehicleSettingResource
VehicleSettingsCache::flush(); // Clear all vehicle settings
```

### Scenario 5: Customer Pays for Trip
```php
// In ProcessPaymentAction
AppStateCache::forgetCustomer($customerId); // No more pending payment
```
