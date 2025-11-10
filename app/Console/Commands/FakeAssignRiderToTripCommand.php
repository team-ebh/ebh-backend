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
        $pendingTrips = Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::PENDING_RIDER)
            ->get();

        if ($pendingTrips->isNotEmpty()) {

            $riders = Rider::all();

            foreach ($pendingTrips as $pendingTrip) {
                $randomRider = $riders->random();

                if ($randomRider) {
                    $pendingTrip->update([
                        Trip::COLUMN_STATUS => TripStatusEnum::ACCEPTED_RIDER,
                        'rider_id' => $randomRider->id,
                    ]);
                }

            }

        }

        return 0;
    }
}
