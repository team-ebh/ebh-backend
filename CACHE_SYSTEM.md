# Entity Caching System

This document describes the comprehensive Redis caching system for App State, Riders, Trips, and Trip Locations.

## Overview

The caching system provides:
- **Entity-specific cache services** for App State (Settings), Riders, Trips, and Trip Locations
- **Background jobs** for asynchronous cache updates
- **Artisan commands** for manual cache management
- **Admin panel actions** for triggering cache updates
- **Live monitoring page** with auto-refresh

## Architecture

### Cache Services

Located in `app/Services/Cache/`:

- **AppStateCacheService** - Manages app state caching (settings and vehicle settings)
- **RiderCacheService** - Manages rider data caching
- **TripCacheService** - Manages trip data caching
- **TripLocationCacheService** - Manages trip location data caching

### Background Jobs

Located in `app/Jobs/Cache/`:

- **UpdateAppStateCacheJob** - Updates app state cache (settings and vehicle settings)
- **UpdateRidersCacheJob** - Updates riders cache
- **UpdateTripsCacheJob** - Updates trips cache
- **UpdateTripLocationsCacheJob** - Updates trip locations cache
- **UpdateAllCachesJob** - Updates all caches (dispatches other jobs)

### Artisan Commands

Available commands for cache management:

```bash
# Update app state cache (settings and vehicle settings)
php artisan cache:update-app-state                         # All app state
php artisan cache:update-app-state --type=settings         # Settings only
php artisan cache:update-app-state --type=vehicle_settings # Vehicle settings only
php artisan cache:update-app-state --async                 # Run in background

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

### Update Actions (Blue/Info)
- **Update App State** - Dispatch job to update settings and vehicle settings cache
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
- **App Settings** count with cache status indicator
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
use App\Services\Cache\AppStateCacheService;
use App\Services\Cache\RiderCacheService;
use App\Services\Cache\TripCacheService;

// Update app state
$appStateCache = app(AppStateCacheService::class);
$appStateCache->updateAll();
// Returns: ['settings' => [...], 'vehicle_settings' => [...], ...]

// Get settings from cache
$settings = $appStateCache->getSettings();

// Get vehicle settings by type
$carTypes = $appStateCache->getVehicleSettingsByType('car_types');

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
use App\Jobs\Cache\UpdateAppStateCacheJob;
use App\Jobs\Cache\UpdateRidersCacheJob;
use App\Jobs\Cache\UpdateTripsCacheJob;
use App\Jobs\Cache\UpdateAllCachesJob;

// Update app state
UpdateAppStateCacheJob::dispatch(type: 'all');

// Update only settings
UpdateAppStateCacheJob::dispatch(type: 'settings');

// Update only vehicle settings
UpdateAppStateCacheJob::dispatch(type: 'vehicle_settings');

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

### App State Cache
- **Key**: `app_state:settings:all` (all settings)
- **Key**: `app_state:vehicle_settings:{type}` (by type)
- **Key**: `app_state:vehicle_settings:all` (all vehicle settings)
- **TTL**: 86400 seconds (24 hours - app state changes rarely)
- **Data**: Settings and vehicle settings (car types, colors, makes, models, passenger capacity)

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
   // Update app state once daily (settings change rarely)
   $schedule->job(new UpdateAppStateCacheJob('all'))->daily();

   // Update entity caches hourly
   $schedule->job(new UpdateAllCachesJob)->hourly();
   ```
3. **Update cache on model events** using observers
4. **Monitor cache hit rates** in monitoring page
5. **Flush stale data** periodically
6. **Update app state cache** whenever settings or vehicle settings change in admin panel

## Performance Considerations

- App state cache: ~10-50 KB total (very small, long TTL)
- Rider cache: ~100-200 KB per rider
- Trip cache: ~50-100 KB per trip
- Location cache: ~20-30 KB per location
- Recommended: Schedule updates during low-traffic periods
- Use `--online` flag for rider updates during peak hours
- App state cache has 24-hour TTL (changes infrequently)

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
