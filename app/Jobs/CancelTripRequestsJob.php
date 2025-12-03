<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Trip\TripRequestStatusEnum;
use App\Events\Socket\Rider\TripCancelledByCustomerEvent;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\Trip;
use App\Models\TripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Cancel Trip Requests Job
 *
 * This job runs in the background when a customer cancels a trip.
 * It handles:
 * - Cancelling all pending and accepted trip requests
 * - Broadcasting cancellation events to all affected riders
 * - Updating rider status from BUSY to ONLINE if trip was assigned
 */
class CancelTripRequestsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $tripId,
        public int $customerId,
        public ?int $riderId = null
    ) {}

    public function handle(RiderTripRepositoryInterface $riderTripRepository): void
    {
        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($riderTripRepository) {
                $trip = Trip::query()->find($this->tripId);

                if (! $trip) {
                    return;
                }

                // Get all pending and accepted trip requests
                $tripRequests = TripRequest::query()
                    ->forTrip($this->tripId)
                    ->whereIn(TripRequest::COLUMN_STATUS, [
                        TripRequestStatusEnum::PENDING->value,
                        TripRequestStatusEnum::ACCEPTED->value,
                    ])
                    ->get();

                // Cancel each trip request and broadcast event
                foreach ($tripRequests as $tripRequest) {
                    // Update status to cancelled
                    $tripRequest->update([
                        TripRequest::COLUMN_STATUS => TripRequestStatusEnum::CANCELLED,
                        TripRequest::COLUMN_RESPONDED_AT => now(),
                    ]);

                    // Broadcast event to rider
                    broadcast(new TripCancelledByCustomerEvent(
                        riderId: $tripRequest->{TripRequest::COLUMN_RIDER_ID},
                        tripId: $this->tripId,
                        customerId: $this->customerId,
                        tripRequestId: $tripRequest->{TripRequest::COLUMN_ID}
                    ));
                }

                // Update rider status from BUSY to ONLINE if trip was assigned to a rider
                if ($this->riderId) {
                    $riderTripRepository->updateRiderStatusToOnline($this->riderId);
                }
            });
    }
}
