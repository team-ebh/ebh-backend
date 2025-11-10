<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Trip\TripStatusEnum;
use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Console\Command;

// TODO: This is a fake command for development/testing only - REMOVE before production
class FakeAssignRiderToTripCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake:assign-riders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[FAKE/DEV ONLY] Assigns random riders to pending trips every 10 seconds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  FAKE COMMAND RUNNING - FOR DEVELOPMENT ONLY');
        $this->info('Checking for pending trips every 10 seconds...');
        $this->info('Press Ctrl+C to stop');
        $this->newLine();

        $iteration = 0;

        while (true) {
            $iteration++;
            $this->info("[{$iteration}] Checking at " . now()->format('Y-m-d H:i:s'));

            // Get all riders
            $riders = Rider::all();

            if ($riders->isEmpty()) {
                $this->warn('No riders found in the database');
                sleep(10);

                continue;
            }

            // Get all pending rider trips
            $pendingTrips = Trip::query()->where(Trip::COLUMN_STATUS, TripStatusEnum::PENDING_RIDER)
                ->get();

            if ($pendingTrips->isEmpty()) {
                $this->comment('No pending trips found');
            } else {
                foreach ($pendingTrips as $trip) {

                    // Get random rider
                    $randomRider = $riders->random();

                    // Update trip status to ACCEPTED_RIDER
                    $trip->update([
                        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
                        // Note: If you have a rider_id column, add it here
                        'rider_id' => $randomRider->id,
                    ]);

                    $this->line("✓ Trip #{$trip->id} assigned to Rider #{$randomRider->id} ({$randomRider->full_name})");
                }

                $this->info("Assigned {$pendingTrips->count()} trip(s)");
            }

            $this->newLine();

            // Wait 10 seconds before next check
            sleep(10);
        }

        return 0;
    }
}
