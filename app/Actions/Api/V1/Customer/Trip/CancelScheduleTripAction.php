<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\TripCannotBeCancelledException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;

/**
 * Cancel Trip Action
 *
 * Cancels the trip and changes status to CANCELLED
 * Only PENDING or CONFIRMED trips can be canceled
 * Dispatches background job to cancel all trip requests and notify riders
 */
readonly class CancelScheduleTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private CustomerTripRepositoryInterface $customerTripRepository,
    ) {}

    /**
     * @throws TripNotBelongToCustomerException
     * @throws TripCannotBeCancelledException
     * @throws \Throwable
     */
    public function __invoke(): void
    {
        $scheduleTrip = $this->customerTripRepository->firstScheduledTrip(auth('customer')->id());

        throw_if(
            ! $scheduleTrip,
            TripCannotBeCancelledException::class,
        );

        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($scheduleTrip) {
                $this->tripRepository->updateStatus(
                    $scheduleTrip,
                    TripStatusEnum::CANCELED_BY_CUSTOMER
                );
            });
    }
}
