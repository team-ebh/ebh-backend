<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ConfirmTripDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripNotPendingException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Services\Trip\TripRequestService;

/**
 * Confirm Trip Action
 *
 * Confirms the trip and changes status to PENDING_RIDER (searching for rider)
 * Sends trip requests to eligible riders
 * Only DRAFT trips can be confirmed
 */
readonly class ConfirmTripAction
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private TripRequestService $tripRequestService,
    ) {}

    /**
     * @throws TripNotPendingException
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
        throw_if(
            ! $dto->trip->isDraft(),
            TripNotPendingException::class
        );

        // Update payment method
        $this->tripRepository->updatePaymentMethod(
            $dto->trip,
            $dto->paymentMethod
        );

        // Update trip status to PENDING_RIDER
        $this->tripRepository->updateStatus(
            $dto->trip,
            TripStatusEnum::PENDING_RIDER
        );

        // Send requests to eligible riders (first attempt with 200m radius)
        $this->tripRequestService->sendRequestsToRiders($dto->trip, searchAttempt: 1);
    }
}
