<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Api\V1\Rider\Trip\AcceptTripRequestAction;
use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Models\Rider;
use App\Models\Trip;
use App\Models\TripRequest;
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
    protected $description = '[FAKE/DEV ONLY] Assigns random online riders to pending trips';

    public function __construct(
        private readonly AcceptTripRequestAction $acceptTripRequestAction
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pendingTrips = Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::PENDING_RIDER)
            ->get();

        if ($pendingTrips->isEmpty()) {
            $this->info('No pending trips found.');

            return 0;
        }

        // Get only online riders
        $onlineRiders = Rider::query()
            ->get()
            ->filter(fn (Rider $rider) => $rider->isOnline());

        if ($onlineRiders->isEmpty()) {
            $this->warn('No online riders available.');

            return 0;
        }

        foreach ($pendingTrips as $pendingTrip) {
            // Get a random online rider
            $randomRider = $onlineRiders->random();

            // Get pending trip request for this trip and rider
            $tripRequest = TripRequest::query()
                ->forTrip($pendingTrip->id)
                ->forRider($randomRider->id)
                ->pending()
                ->first();

            if (! $tripRequest) {
                $this->warn("No trip request found for trip {$pendingTrip->id} and rider {$randomRider->id}");

                continue;
            }

            // Create DTO and accept trip
            try {
                $dto = new AcceptTripRequestDTO();
                $dto->riderId = $randomRider->id;
                $dto->tripRequest = $tripRequest;

                $this->acceptTripRequestAction->acceptTrip($dto);

                $this->info("Trip {$pendingTrip->id} assigned to rider {$randomRider->id}");
            } catch (\Throwable $e) {
                $this->error("Failed to assign trip {$pendingTrip->id} to rider {$randomRider->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
