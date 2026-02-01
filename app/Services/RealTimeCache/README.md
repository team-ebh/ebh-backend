# RealTime Cache Service

A high-performance real-time data management service for storing rider locations, online/offline status, and trip information using Redis with automatic database fallback.

## Features

- ✅ Store and query rider locations using Redis GEO
- ✅ Manage rider status (online/available/busy/offline)
- ✅ Cache trip data during active rides
- ✅ Distributed locking to prevent race conditions
- ✅ Automatic fallback to database when Redis is unavailable
- ✅ Circuit breaker pattern for intelligent error handling

---

## Installation

The service is automatically registered via `RealTimeCacheServiceProvider`.

### Environment Variables (Optional)

```env
# Redis Connection
REALTIME_CACHE_REDIS_CONNECTION=default
REALTIME_CACHE_PREFIX=rtc:

# TTL Settings (seconds)
RTC_RIDER_LOCATION_TTL=30
RTC_RIDER_STATUS_TTL=60
RTC_TRIP_TTL_PENDING=600
RTC_TRIP_TTL_COMPLETED=1800

# Fallback Settings
RTC_FALLBACK_ENABLED=true
RTC_FALLBACK_COOLDOWN=30

# Feature Flags (disable to use Database instead)
RTC_ENABLED=true                # Master switch: false = database only
RTC_RIDER_GEOLOCATION=true      # Rider GPS location tracking
RTC_RIDER_ONLINE_STATUS=true    # Rider online/offline/busy status
RTC_TRIP_CACHE=true             # Active trip data caching
RTC_ASSIGNMENT_LOCK=true        # Distributed locking for rider assignment
```

---

## Usage

### 1. Dependency Injection

```php
use App\Services\RealTimeCache\RealTimeCacheManager;

class YourAction
{
    public function __construct(
        private RealTimeCacheManager $cache
    ) {}
}
```

Or using the container:

```php
$cache = app(RealTimeCacheManager::class);
```

---

### 2. Find Nearby Online Riders (Convenience Method)

```php
// Find online riders within 500 meters
$riders = $this->cache->findNearbyOnlineRiders(
    center: Coordinate::make(29.3759, 47.9774),
    radius: Distance::meters(500)
);

// Find only ready riders (exclude busy)
$readyRiders = $this->cache->findNearbyOnlineRiders(
    center: Coordinate::make(29.3759, 47.9774),
    radius: Distance::meters(500),
    excludeBusy: true,
    limit: 10
);

// Returns:
// [
//     ['rider_id' => 123, 'distance' => 150.5, 'coordinate' => Coordinate, 'status' => 'online'],
//     ['rider_id' => 456, 'distance' => 320.0, 'coordinate' => Coordinate, 'status' => 'busy'],
// ]
```

---

### 3. Location Storage (Low-Level)

```php
use App\Services\RealTimeCache\ValueObjects\Coordinate;
use App\Services\RealTimeCache\ValueObjects\Distance;

// Store rider location
$this->cache->location()->store(
    riderId: 123,
    coordinate: Coordinate::make(29.3759, 47.9774),
    ttl: 30 // optional, defaults to config value
);

// Get rider location
$coordinate = $this->cache->location()->get(123);
if ($coordinate) {
    echo $coordinate->lat(); // 29.3759
    echo $coordinate->lng(); // 47.9774
}

// Find nearby riders
$nearbyRiders = $this->cache->location()->findNearby(
    center: Coordinate::make(29.3759, 47.9774),
    radius: Distance::meters(5000), // 5 kilometers
    limit: 10
);

// Returns:
// [
//     ['rider_id' => 123, 'distance' => 850.5, 'coordinate' => Coordinate],
//     ['rider_id' => 456, 'distance' => 1200.0, 'coordinate' => Coordinate],
// ]

// Get all rider IDs with stored locations
$allRiderIds = $this->cache->location()->getAllRiderIds();

// Remove location
$this->cache->location()->remove(123);

// Check if exists
if ($this->cache->location()->exists(123)) {
    // ...
}

// Count stored locations
$count = $this->cache->location()->count();
```

---

### 4. Status Storage

Rider statuses: `online`, `busy`, `offline`

- **online**: Rider is online and ready to accept trips
- **busy**: Rider is currently on a trip
- **offline**: Rider is not working (removed from cache)

```php
// Set status with metadata
$this->cache->status()->setStatus(
    riderId: 123,
    status: 'online', // or 'busy'
    meta: ['vehicle_id' => 1, 'car_type' => 'sedan'],
    ttl: 60 // optional
);

// Or use helper methods
$this->cache->status()->setOnline(123, ['vehicle_id' => 1]);
$this->cache->status()->setBusy(123, ['trip_id' => 456]);
$this->cache->status()->setOffline(123);

// Get status
$status = $this->cache->status()->getStatus(123); // 'online' | 'busy' | null

// Get metadata
$meta = $this->cache->status()->getMeta(123);
// ['status' => 'online', 'vehicle_id' => 1, 'last_seen' => 1234567890]

// Heartbeat (refresh TTL)
$this->cache->status()->heartbeat(123);

// Check status
if ($this->cache->status()->isOnline(123)) { }  // true if online OR busy
if ($this->cache->status()->isBusy(123)) { }    // true only if busy

// Get rider lists by status
$onlineIds = $this->cache->status()->getOnlineRiderIds();  // All online (including busy)
$readyIds = $this->cache->status()->getReadyRiderIds();    // Online but NOT busy
$busyIds = $this->cache->status()->getBusyRiderIds();      // Only busy

// Count by status
$onlineCount = $this->cache->status()->countOnline();  // All online (including busy)
$readyCount = $this->cache->status()->countReady();    // Online but NOT busy
$busyCount = $this->cache->status()->countBusy();      // Only busy
```

---

### 5. Trip Storage

```php
// Store trip data
$this->cache->trip()->store(
    tripId: 456,
    data: [
        'rider_id' => 123,
        'customer_id' => 789,
        'pickup_lat' => 29.3759,
        'pickup_lng' => 47.9774,
        'dropoff_lat' => 29.3800,
        'dropoff_lng' => 47.9800,
        'fare' => 5.500,
    ],
    status: 'pending' // TTL is set based on status
);

// Get trip data
$tripData = $this->cache->trip()->get(456);

// Update data (merges with existing)
$this->cache->trip()->update(456, [
    'driver_arrived_at' => time(),
]);

// Update status (also updates TTL)
$this->cache->trip()->updateStatus(456, 'accepted');
$this->cache->trip()->updateStatus(456, 'arrived');
$this->cache->trip()->updateStatus(456, 'picked_up');
$this->cache->trip()->updateStatus(456, 'completed');

// Set active trip for rider/customer
$this->cache->trip()->setRiderActiveTrip(123, 456);
$this->cache->trip()->setCustomerActiveTrip(789, 456);

// Get active trip
$tripId = $this->cache->trip()->getRiderActiveTrip(123);
$tripId = $this->cache->trip()->getCustomerActiveTrip(789);

// Clear active trip
$this->cache->trip()->clearRiderActiveTrip(123);
$this->cache->trip()->clearCustomerActiveTrip(789);

// Remove trip from cache
$this->cache->trip()->remove(456);

// List all active trip IDs
$tripIds = $this->cache->trip()->getAllTripIds();
```

---

### 6. Lock Manager

```php
$requestId = uniqid('req_');

// Lock a rider (prevent simultaneous assignment)
if ($this->cache->lock()->lockRider(123, $requestId, ttlMs: 5000)) {
    try {
        // Perform assignment
    } finally {
        $this->cache->lock()->unlockRider(123, $requestId);
    }
}

// Lock a trip
if ($this->cache->lock()->lockTrip(456, $requestId)) {
    // ...
    $this->cache->lock()->unlockTrip(456, $requestId);
}

// Generic lock
if ($this->cache->lock()->acquire('custom:key', $requestId, ttlMs: 10000)) {
    // ...
    $this->cache->lock()->release('custom:key', $requestId);
}

// Check lock status
if ($this->cache->lock()->isLocked('rider:123')) {
    $owner = $this->cache->lock()->getOwner('rider:123');
}

// Extend lock TTL
$this->cache->lock()->extend('rider:123', $requestId, ttlMs: 5000);

// Force release (without owner check)
$this->cache->lock()->forceRelease('rider:123');
```

---

### 7. Statistics & Monitoring

```php
// Get overall statistics
$stats = $this->cache->getStats();
// [
//     'locations' => ['count' => 150],
//     'status' => ['online' => 120, 'ready' => 80, 'busy' => 40],
//     'trips' => ['count' => 25],
// ]

// Flush all data (use with caution!)
$this->cache->flushAll();
```

---

## Value Objects

### Coordinate

```php
use App\Services\RealTimeCache\ValueObjects\Coordinate;

// Create
$coord = Coordinate::make(29.3759, 47.9774);
$coord = Coordinate::fromArray(['lat' => 29.3759, 'lng' => 47.9774]);
$coord = Coordinate::fromString('29.3759,47.9774');

// Access
$coord->lat();       // 29.3759
$coord->lng();       // 47.9774
$coord->toArray();   // ['lat' => 29.3759, 'lng' => 47.9774]
$coord->toString();  // '29.3759,47.9774'

// Calculate distance
$distance = $coord->distanceTo($otherCoord); // meters

// Validate
if ($coord->isValid()) { }
```

### Distance

```php
use App\Services\RealTimeCache\ValueObjects\Distance;

// Create
$distance = Distance::meters(5000);
$distance = Distance::kilometers(5);

// Convert
$distance->toMeters();      // 5000.0
$distance->toMetersInt();   // 5000
$distance->toKilometers();  // 5.0

// Compare
$distance->greaterThan(Distance::meters(3000)); // true
$distance->lessThan(Distance::kilometers(10));  // true
$distance->isWithin(Distance::kilometers(10));  // true

// Format
$distance->format(); // '5 km' or '500 m'
```

---

## Fallback Behavior

When Redis is unavailable, the system automatically switches to database storage.

### How It Works

```
Request → RedisHealthChecker → Redis OK? → Use Redis
                                    ↓ No
                              Use Database
                                    ↓
                              Log Warning
                                    ↓
                              Set Cooldown (30s)
```

### Disable Fallback

```env
RTC_FALLBACK_ENABLED=false
```

### Change Cooldown Period

```env
RTC_FALLBACK_COOLDOWN=60  # 60 seconds
```

---

## Monitoring

Access the monitoring page in the Admin Panel:

```
http://admin.localhost:9000/realtime-cache-monitor
```

The page displays:
- Location count, online riders, active trips
- Redis status (Primary/Fallback)
- Redis server information
- List of cached riders and trips

---

## Redis Keys Structure

```bash
# Location
rtc:geo:riders                    # GEO set
rtc:rider:{id}:loc                # TTL tracking

# Status
rtc:rider:{id}:status             # HASH
rtc:riders:online                 # SET
rtc:riders:available              # SET
rtc:riders:busy                   # SET

# Trip
rtc:trip:{id}                     # HASH
rtc:trips:active                  # SET
rtc:rider:{id}:trip               # STRING
rtc:customer:{id}:trip            # STRING

# Lock
rtc:lock:{key}                    # STRING with TTL
```

---

## Example: Complete Trip Flow

```php
class TripFlowExample
{
    public function __construct(
        private RealTimeCacheManager $cache
    ) {}

    public function requestTrip(int $customerId, float $pickupLat, float $pickupLng): void
    {
        $pickup = Coordinate::make($pickupLat, $pickupLng);

        // Find nearby riders
        $nearbyRiders = $this->cache->location()->findNearby(
            center: $pickup,
            radius: Distance::kilometers(5),
            limit: 10
        );

        // Filter only ready riders (online but not busy)
        $readyRiders = array_filter($nearbyRiders, function ($rider) {
            return $this->cache->status()->isOnline($rider['rider_id'])
                && !$this->cache->status()->isBusy($rider['rider_id']);
        });

        foreach ($readyRiders as $rider) {
            // Try to lock the rider
            if ($this->cache->lock()->lockRider($rider['rider_id'], "trip_request_{$customerId}")) {
                // Send request to rider
                $this->sendRequestToRider($rider['rider_id'], $customerId);
                break;
            }
        }
    }

    public function acceptTrip(int $tripId, int $riderId): void
    {
        // Store in cache
        $this->cache->trip()->store($tripId, [
            'rider_id' => $riderId,
            'status' => 'accepted',
        ], 'accepted');

        // Set rider status to busy
        $this->cache->status()->setBusy($riderId, ['trip_id' => $tripId]);

        // Set active trip
        $this->cache->trip()->setRiderActiveTrip($riderId, $tripId);

        // Release the lock
        $this->cache->lock()->forceRelease("rider:{$riderId}");
    }

    public function updateRiderLocation(int $riderId, float $lat, float $lng): void
    {
        $coord = Coordinate::make($lat, $lng);

        // Update location
        $this->cache->location()->store($riderId, $coord);

        // Heartbeat for status TTL
        $this->cache->status()->heartbeat($riderId);
    }

    public function completeTrip(int $tripId, int $riderId): void
    {
        // Update trip status
        $this->cache->trip()->updateStatus($tripId, 'completed');

        // Set rider back to online (ready for new trips)
        $this->cache->status()->setOnline($riderId);

        // Clear active trip
        $this->cache->trip()->clearRiderActiveTrip($riderId);
    }
}
```

---

## Testing

You can use mocks for testing:

```php
use App\Services\RealTimeCache\Contracts\LocationStorageInterface;

$mock = Mockery::mock(LocationStorageInterface::class);
$mock->shouldReceive('findNearby')->andReturn([
    ['rider_id' => 1, 'distance' => 500, 'coordinate' => Coordinate::make(29.0, 47.0)],
]);

$this->app->instance(LocationStorageInterface::class, $mock);
```

---

## Extending

To add a new driver (e.g., Tile38):

1. Create a new class implementing the interface
2. Register it in `RealTimeCacheServiceProvider`
3. Update the config driver setting

```php
// app/Services/RealTimeCache/Drivers/Tile38/Tile38LocationStorage.php
class Tile38LocationStorage implements LocationStorageInterface
{
    // Implementation...
}
```
