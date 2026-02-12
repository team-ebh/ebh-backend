<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider;

use App\DTOs\Api\V1\Rider\UpdateRiderStatusDTO;
use App\Exceptions\Rider\CannotChangeRiderStatusException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\RiderCache;

/**
 * Update Rider Status Action
 *
 * Allows rider to change their status between ONLINE and OFFLINE
 * Cannot change status if:
 * - Currently BUSY
 * - Has an active trip
 */
readonly class UpdateRiderStatusAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository
    ) {}

    /**
     * @throws CannotChangeRiderStatusException
     * @throws \Throwable
     */
    public function __invoke(UpdateRiderStatusDTO $dto): void
    {
        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'updateStatus'], $dto);

        // Clear caches after status change
        AppStateCache::forgetRider($dto->riderId);
        RiderCache::forgetRider($dto->riderId);
    }

    /**
     * Update rider status
     *
     * @throws CannotChangeRiderStatusException
     * @throws \Throwable
     */
    public function updateStatus(UpdateRiderStatusDTO $dto): void
    {
        $rider = $this->riderTripRepository->getRider($dto->riderId);

        throw_if(
            $rider->isBusy(),
            CannotChangeRiderStatusException::class
        );

        throw_if(
            $this->riderTripRepository->existsActiveTrip($dto->riderId),
            CannotChangeRiderStatusException::class
        );

        $this->riderTripRepository->updateRiderStatus($rider, $dto->status);
    }
}
