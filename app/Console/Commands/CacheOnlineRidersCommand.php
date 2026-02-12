<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use App\Services\Cache\RiderCache;
use Illuminate\Console\Command;

class CacheOnlineRidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:online-riders
                          {--online : Cache only online riders (default)}
                          {--busy : Cache only busy riders}
                          {--active : Cache both online and busy riders}
                          {--all : Cache all riders (online, busy, and offline)}
                          {--fresh : Clear existing cache before caching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache online riders data into Redis for faster access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting to cache riders...');
        $this->newLine();

        // Clear cache if --fresh flag is provided
        if ($this->option('fresh')) {
            $this->warn('🗑️  Clearing existing rider cache...');
            RiderCache::flush();
            $this->info('✓ Cache cleared');
            $this->newLine();
        }

        // Determine which riders to cache based on options
        if ($this->option('all')) {
            $this->cacheAllRiders();
        } elseif ($this->option('busy')) {
            $this->cacheBusyRiders();
        } elseif ($this->option('active')) {
            $this->cacheActiveRiders();
        } else {
            // Default: cache online riders
            $this->cacheOnlineRiders();
        }

        $this->newLine();
        $this->info('✅ Caching completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Cache only online riders
     */
    protected function cacheOnlineRiders(): void
    {
        $this->info('📋 Fetching online riders from database...');

        $riders = Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::ONLINE)
            ->where(Rider::COLUMN_ENABLED, true)
            ->get([
                'id',
                Rider::COLUMN_FULL_NAME,
                Rider::COLUMN_PHONE_NUMBER,
                Rider::COLUMN_STATUS,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
            ]);

        if ($riders->isEmpty()) {
            $this->warn('⚠️  No online riders found in database');

            return;
        }

        $this->info("Found {$riders->count()} online riders");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($riders->count());
        $progressBar->start();

        $cached = 0;
        $riderCache = new RiderCache;

        foreach ($riders as $rider) {
            $riderCache->get($rider->id, fn () => [
                'rider_id' => $rider->id,
                'name' => $rider->{Rider::COLUMN_FULL_NAME},
                'phone' => $rider->{Rider::COLUMN_PHONE_NUMBER},
                'status' => $rider->{Rider::COLUMN_STATUS}->value,
                'lat' => $rider->{Rider::COLUMN_LATITUDE},
                'lng' => $rider->{Rider::COLUMN_LONGITUDE},
            ]);

            $cached++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Cached {$cached} online riders");
    }

    /**
     * Cache only busy riders
     */
    protected function cacheBusyRiders(): void
    {
        $this->info('📋 Fetching busy riders from database...');

        $riders = Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::BUSY)
            ->where(Rider::COLUMN_ENABLED, true)
            ->get([
                'id',
                Rider::COLUMN_FULL_NAME,
                Rider::COLUMN_PHONE_NUMBER,
                Rider::COLUMN_STATUS,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
            ]);

        if ($riders->isEmpty()) {
            $this->warn('⚠️  No busy riders found in database');

            return;
        }

        $this->info("Found {$riders->count()} busy riders");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($riders->count());
        $progressBar->start();

        $cached = 0;
        $riderCache = new RiderCache;

        foreach ($riders as $rider) {
            $riderCache->get($rider->id, fn () => [
                'rider_id' => $rider->id,
                'name' => $rider->{Rider::COLUMN_FULL_NAME},
                'phone' => $rider->{Rider::COLUMN_PHONE_NUMBER},
                'status' => $rider->{Rider::COLUMN_STATUS}->value,
                'lat' => $rider->{Rider::COLUMN_LATITUDE},
                'lng' => $rider->{Rider::COLUMN_LONGITUDE},
            ]);

            $cached++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Cached {$cached} busy riders");
    }

    /**
     * Cache both online and busy riders (active riders)
     */
    protected function cacheActiveRiders(): void
    {
        $this->info('📋 Fetching active riders (online & busy) from database...');

        $riders = Rider::query()
            ->whereIn(Rider::COLUMN_STATUS, [RiderStatusEnum::ONLINE, RiderStatusEnum::BUSY])
            ->where(Rider::COLUMN_ENABLED, true)
            ->get([
                'id',
                Rider::COLUMN_FULL_NAME,
                Rider::COLUMN_PHONE_NUMBER,
                Rider::COLUMN_STATUS,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
            ]);

        if ($riders->isEmpty()) {
            $this->warn('⚠️  No active riders found in database');

            return;
        }

        $this->info("Found {$riders->count()} active riders");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($riders->count());
        $progressBar->start();

        $cached = 0;
        $statusCounts = [
            'online' => 0,
            'busy' => 0,
        ];

        $riderCache = new RiderCache;

        foreach ($riders as $rider) {
            $riderCache->get($rider->id, fn () => [
                'rider_id' => $rider->id,
                'name' => $rider->{Rider::COLUMN_FULL_NAME},
                'phone' => $rider->{Rider::COLUMN_PHONE_NUMBER},
                'status' => $rider->{Rider::COLUMN_STATUS}->value,
                'lat' => $rider->{Rider::COLUMN_LATITUDE},
                'lng' => $rider->{Rider::COLUMN_LONGITUDE},
            ]);

            // Count by status
            match ($rider->{Rider::COLUMN_STATUS}) {
                RiderStatusEnum::ONLINE => $statusCounts['online']++,
                RiderStatusEnum::BUSY => $statusCounts['busy']++,
                default => null,
            };

            $cached++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Cached {$cached} active riders:");
        $this->line("  - Online: {$statusCounts['online']}");
        $this->line("  - Busy: {$statusCounts['busy']}");
    }

    /**
     * Cache all riders (online, busy, offline)
     */
    protected function cacheAllRiders(): void
    {
        $this->info('📋 Fetching all riders from database...');

        $riders = Rider::query()
            ->where(Rider::COLUMN_ENABLED, true)
            ->get([
                'id',
                Rider::COLUMN_FULL_NAME,
                Rider::COLUMN_PHONE_NUMBER,
                Rider::COLUMN_STATUS,
                Rider::COLUMN_LATITUDE,
                Rider::COLUMN_LONGITUDE,
            ]);

        if ($riders->isEmpty()) {
            $this->warn('⚠️  No riders found in database');

            return;
        }

        $this->info("Found {$riders->count()} riders");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($riders->count());
        $progressBar->start();

        $cached = 0;
        $statusCounts = [
            'online' => 0,
            'busy' => 0,
            'offline' => 0,
        ];

        $riderCache = new RiderCache;

        foreach ($riders as $rider) {
            $riderCache->get($rider->id, fn () => [
                'rider_id' => $rider->id,
                'name' => $rider->{Rider::COLUMN_FULL_NAME},
                'phone' => $rider->{Rider::COLUMN_PHONE_NUMBER},
                'status' => $rider->{Rider::COLUMN_STATUS}->value,
                'lat' => $rider->{Rider::COLUMN_LATITUDE},
                'lng' => $rider->{Rider::COLUMN_LONGITUDE},
            ]);

            // Count by status
            match ($rider->{Rider::COLUMN_STATUS}) {
                RiderStatusEnum::ONLINE => $statusCounts['online']++,
                RiderStatusEnum::BUSY => $statusCounts['busy']++,
                RiderStatusEnum::OFFLINE => $statusCounts['offline']++,
                default => null,
            };

            $cached++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Cached {$cached} riders:");
        $this->line("  - Online: {$statusCounts['online']}");
        $this->line("  - Busy: {$statusCounts['busy']}");
        $this->line("  - Offline: {$statusCounts['offline']}");
    }
}
