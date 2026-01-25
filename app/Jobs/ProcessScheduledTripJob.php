<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Events\Socket\Customer\TripSearchingForRiderEvent;
use App\Interfaces\Repositories\Api\V1\Customer\OrderRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Scheduled Trip Job
 *
 * This job runs at the scheduled time of a trip.
 * It checks if the trip is still valid (not cancelled) and:
 * - Creates order if not exists
 * - Changes status to PENDING_RIDER
 * - Sends trip requests to eligible riders
 */
class ProcessScheduledTripJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    public function __construct(
        public readonly int $tripId,
        public readonly ?PaymentMethodEnum $paymentMethod = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        TripRequestService $tripRequestService,
        OrderRepositoryInterface $orderRepository
    ): void {
        DB::transaction(function () use ($tripRequestService, $orderRepository) {
            // Lock the trip for update to prevent race conditions
            $trip = Trip::query()
                ->where(Trip::COLUMN_ID, $this->tripId)
                ->lockForUpdate()
                ->first();

            if (! $trip) {
                return;
            }

            // Check if trip is still in a valid state to process
            if (! $this->isValidForProcessing($trip)) {
                return;
            }

            // Create order if not exists
            if (! $trip->{Trip::COLUMN_ORDER_ID}) {
                $this->createOrderForTrip($trip, $orderRepository);
            }

            // Update trip status to PENDING_RIDER
            $trip->update([
                Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
            ]);

            // Send requests to eligible riders
            $tripRequestService->sendRequestsToRiders($trip, searchAttempt: 1);

            broadcast(new TripSearchingForRiderEvent(
                customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
                tripId: $trip->{Trip::COLUMN_ID},
            ));
        });
    }

    /**
     * Create order for trip if not exists
     */
    private function createOrderForTrip(Trip $trip, OrderRepositoryInterface $orderRepository): void
    {
        $paymentMethod = $this->paymentMethod ?? PaymentMethodEnum::CASH;

        $order = $orderRepository->createOrder(
            customerId: $trip->{Trip::COLUMN_CUSTOMER_ID},
            totalPrice: (float) $trip->{Trip::COLUMN_TOTAL_PRICE},
            currency: $trip->{Trip::COLUMN_CURRENCY},
            paymentMethod: $paymentMethod
        );

        $trip->update([
            Trip::COLUMN_ORDER_ID => $order->{$order::COLUMN_ID},
        ]);
    }

    /**
     * Check if trip is valid for processing
     *
     * Trip must be in DRAFT status (not cancelled, not already processed)
     */
    private function isValidForProcessing(Trip $trip): bool
    {
        $status = $trip->{Trip::COLUMN_STATUS};

        // Only process trips that are still in DRAFT status
        // If cancelled, completed, or already pending rider - skip
        return $status === TripStatusEnum::DRAFT;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessScheduledTripJob] Job failed', [
            'trip_id' => $this->tripId,
            'error' => $exception->getMessage(),
        ]);
    }
}
