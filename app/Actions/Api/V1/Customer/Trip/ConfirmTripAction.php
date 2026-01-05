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
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\SendRiderRequestsPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\UpdatePaymentAndStatusPipe;
use App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip\ValidateTripPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Confirm Trip Action
 *
 * Confirms the trip and changes status to PENDING_RIDER (searching for rider)
 * Sends trip requests to eligible riders using pipeline pattern
 * Only DRAFT trips can be confirmed
 * Customer must have paid for their last trip before confirming a new one
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
    }

    /**
     * Confirm the trip and send requests to riders
     *
     * @throws \Throwable
     */
    public function confirmTrip(ConfirmTripDTO $dto): void
    {
        $context = new ConfirmTripContext($dto);

        app(Pipeline::class)
            ->send($context)
            ->through([
                ValidateTripPipe::class,
                CreateDestinationLocationPipe::class,
                CreateDemandTripPipe::class,
                UpdatePaymentAndStatusPipe::class,
                SendRiderRequestsPipe::class,
            ])
            ->thenReturn();
    }
}
