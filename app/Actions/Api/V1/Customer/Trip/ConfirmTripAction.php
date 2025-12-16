<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\CustomerHasUnpaidTripException;
use App\Exceptions\Trip\TripNotPendingException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use App\Services\Trip\TripRequestService;

/**
 * Confirm Trip Action
 *
 * Confirms the trip and changes status to PENDING_RIDER (searching for rider)
 * Sends trip requests to eligible riders
 * Only DRAFT trips can be confirmed
 * Customer must have paid for their last trip before confirming a new one
 */
readonly class ConfirmTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private TripRequestService $tripRequestService,
    ) {}

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
        // Validate trip belongs to authenticated customer
        throw_if(
            ! $dto->trip->belongsToCustomer($dto->customerId),
            TripNotBelongToCustomerException::class
        );

        throw_if(
            ! $dto->trip->isDraft(),
            TripNotPendingException::class
        );

        // Check if customer has unpaid trips
        $lastTrip = $this->tripRepository->getLastTrip($dto->customerId);
        throw_if(
            $lastTrip && ! $lastTrip->hasPaidPayment(),
            CustomerHasUnpaidTripException::class
        );

        // Update payment method and trip status to PENDING_RIDER
        $this->tripRepository->updatePaymentMethodAndStatus(
            $dto->trip,
            $dto->paymentMethod,
            TripStatusEnum::PENDING_RIDER
        );

        // Send requests to eligible riders (first attempt with 200m radius)
        $this->tripRequestService->sendRequestsToRiders($dto->trip, searchAttempt: 1);
    }
}
