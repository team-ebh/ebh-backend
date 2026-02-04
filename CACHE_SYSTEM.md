# Entity Caching System

This document describes the comprehensive Redis caching system for Riders, Trips, and Trip Locations.

## Overview

The caching system provides:
- **Entity-specific cache services** for Riders, Trips, and Trip Locations
- **Background jobs** for asynchronous cache updates
- **Artisan commands** for manual cache management
- **Admin panel actions** for triggering cache updates
- **Live monitoring page** with auto-refresh

## Architecture

### Cache Services

Located in `app/Services/Cache/`:

- **RiderCacheService** - Manages rider data caching
- **TripCacheService** - Manages trip data caching
- **TripLocationCacheService** - Manages trip location data caching

### Background Jobs

Located in `app/Jobs/Cache/`:

- **UpdateRidersCacheJob** - Updates riders cache
- **UpdateTripsCacheJob** - Updates trips cache
- **UpdateTripLocationsCacheJob** - Updates trip locations cache
- **UpdateAllCachesJob** - Updates all caches (dispatches other jobs)

### Artisan Commands

Available commands for cache management:

```bash
# Update riders cache
php artisan cache:update-riders                # All riders
php artisan cache:update-riders --online       # Online riders only
php artisan cache:update-riders --async        # Run in background

# Update trips cache
php artisan cache:update-trips                 # Active trips (default)
php artisan cache:update-trips --all           # All trips (last 7 days)
php artisan cache:update-trips --async         # Run in background

# Update trip locations cache
php artisan cache:update-trip-locations                    # All locations
php artisan cache:update-trip-locations --trip-id=123      # Specific trip
php artisan cache:update-trip-locations --async            # Run in background

# Update all caches
php artisan cache:update-all                   # Update all entity caches
php artisan cache:update-all --async           # Run all updates in background
```

## Admin Panel Actions

Access the **RealTime Cache Monitor** page in admin panel (Settings → RealTime Cache).

Available actions:

### Update Actions (Green)
- **Update Riders** - Dispatch job to update all riders cache
- **Update Trips** - Dispatch job to update active trips cache
- **Update Trip Locations** - Dispatch job to update trip locations cache
- **Update All Caches** - Dispatch jobs to update all caches

### Flush Actions (Warning/Danger)
- **Flush Locations** - Remove all rider location data
- **Flush Statuses** - Remove all rider status data
- **Flush Trips** - Remove all trip data
- **Flush All** - Remove ALL cache data

## Monitoring Page

The monitoring page features:

### Live Updates
- **Auto-refresh every 10 seconds** using Livewire polling
- Real-time statistics display
- Last refresh timestamp

### Statistics Dashboard
- Rider Locations count
- Online Riders count
- Ready Riders count
- Active Trips count

### Data Tables
- **Rider Locations** - Shows up to 50 recent locations
- **Rider Statuses** - Shows up to 50 online riders with status badges
- **Active Trips** - Shows up to 50 active trips with status

## Programmatic Usage

### Using Cache Services

```php
use App\Services\Cache\RiderCacheService;
use App\Services\Cache\TripCacheService;

// Update single rider
$riderCache = app(RiderCacheService::class);
$riderCache->updateRider($riderId);

// Update all riders
$stats = $riderCache->updateAllRiders();
// Returns: ['total' => 100, 'success' => 98, 'failed' => 2]

// Get rider from cache
$cachedRider = $riderCache->getRider($riderId);

// Update single trip
$tripCache = app(TripCacheService::class);
$tripCache->updateTrip($tripId);

// Update active trips only
$stats = $tripCache->updateActiveTrips();
```

### Dispatching Background Jobs

```php
use App\Jobs\Cache\UpdateRidersCacheJob;
use App\Jobs\Cache\UpdateTripsCacheJob;
use App\Jobs\Cache\UpdateAllCachesJob;

// Update riders in background
UpdateRidersCacheJob::dispatch(onlineOnly: false);

// Update only online riders
UpdateRidersCacheJob::dispatch(onlineOnly: true);

// Update active trips
UpdateTripsCacheJob::dispatch(activeOnly: true);

// Update all trips (last 7 days)
UpdateTripsCacheJob::dispatch(activeOnly: false);

// Update everything
UpdateAllCachesJob::dispatch();
```

## Cache Structure

### Rider Cache
- **Key**: `rider:{rider_id}`
- **TTL**: 3600 seconds (1 hour)
- **Data**: Full rider data including vehicle and company info

### Trip Cache
- **Key**: `trip:{trip_id}`
- **TTL**: 1800 seconds (30 minutes)
- **Data**: Trip data including customer, rider, and locations

### Trip Location Cache
- **Key**: `trip_location:{location_id}`
- **Key**: `trip_locations:trip:{trip_id}` (all locations for a trip)
- **TTL**: 1800 seconds (30 minutes)
- **Data**: Location coordinates and status

## Integration with RealTimeCache

The entity caching system integrates with the existing `RealTimeCacheManager`:

- **Rider locations** are synced to RealTimeCache for proximity searches
- **Rider statuses** are synced to RealTimeCache for online/busy tracking
- **Active trips** are synced to RealTimeCache for real-time updates

## Best Practices

1. **Use background jobs** for large cache updates (`--async` flag)
2. **Schedule regular updates** in `app/Console/Kernel.php`:
   ```php
   $schedule->job(new UpdateAllCachesJob)->hourly();
   ```
3. **Update cache on model events** using observers
4. **Monitor cache hit rates** in monitoring page
5. **Flush stale data** periodically

## Performance Considerations

- Rider cache: ~100-200 KB per rider
- Trip cache: ~50-100 KB per trip
- Location cache: ~20-30 KB per location
- Recommended: Schedule updates during low-traffic periods
- Use `--online` flag for rider updates during peak hours

## Troubleshooting

### Cache Not Updating
```bash
# Check job queue is running
php artisan queue:work

# Manually update cache
php artisan cache:update-all

# Check logs
tail -f storage/logs/laravel.log | grep cache
```

### High Memory Usage
```bash
# Update only online riders
php artisan cache:update-riders --online

# Update only active trips
php artisan cache:update-trips
```

### Stale Cache Data
```bash
# Flush and rebuild
php artisan cache:update-all
```
