<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\CustomerHasUnpaidTripException;
use App\Exceptions\Trip\TripNotPendingException;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\ConfirmTripContext;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\CreateDemandTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\CreateDestinationLocationPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\CreateScheduledTripOrderPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\DispatchDemandTripJobPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\DispatchScheduledTripJobPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\SendRiderRequestsPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\UpdatePaymentAndStatusPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\ValidateScheduledTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\ValidateTripPipe;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\TripCache;
use Illuminate\Pipeline\Pipeline;

/**
 * Confirm Trip Action
 *
 * Confirms the trip based on its type:
 * - RIDE_NOW: Creates order, changes status to PENDING_RIDER, sends rider requests immediately
 * - SCHEDULED: Creates order (keeps status as DRAFT), dispatches delayed job to process at scheduled time
 *
 * Only DRAFT trips can be confirmed.
 * Customer must have paid for their last trip before confirming a new one.
 */
readonly class ConfirmTripAction
{
    /**
     * @throws TripNotBelongToCustomerException
     * @throws TripNotPendingException
     * @throws CustomerHasUnpaidTripException
     * @throws \Throwable
     */
    public function __invoke(ConfirmTripDTO $dto): void
    {
        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'confirmTrip'], $dto);

        // Clear customer cache after trip confirmation
        AppStateCache::forgetCustomer($dto->customerId);
        TripCache::forgetCustomer($dto->customerId);
    }

    /**
     * Confirm the trip based on its type
     *
     * @throws \Throwable
     */
    public function confirmTrip(ConfirmTripDTO $dto): void
    {
        $context = new ConfirmTripContext($dto);

        // Branch based on trip type
        if ($dto->trip->isScheduledTripType()) {
            $this->confirmScheduledTrip($context);
        } else {
            $this->confirmRideNowTrip($context);
        }
    }

    /**
     * Confirm a RIDE_NOW trip
     *
     * Creates order, sets status to PENDING_RIDER, sends rider requests immediately.
     * Also handles round trips with demand trip creation.
     */
    private function confirmRideNowTrip(ConfirmTripContext $context): void
    {
        app(Pipeline::class)
            ->send($context)
            ->through([
                ValidateTripPipe::class,
                CreateDestinationLocationPipe::class,
                CreateDemandTripPipe::class,
                UpdatePaymentAndStatusPipe::class,
                SendRiderRequestsPipe::class,
                DispatchDemandTripJobPipe::class,
            ])
            ->thenReturn();
    }

    /**
     * Confirm a SCHEDULED trip
     *
     * Creates order but keeps status as DRAFT.
     * Dispatches a delayed job to process the trip at its scheduled time.
     */
    private function confirmScheduledTrip(ConfirmTripContext $context): void
    {
        app(Pipeline::class)
            ->send($context)
            ->through([
                ValidateScheduledTripPipe::class,
                CreateScheduledTripOrderPipe::class,
                DispatchScheduledTripJobPipe::class,
            ])
            ->thenReturn();
    }
}
