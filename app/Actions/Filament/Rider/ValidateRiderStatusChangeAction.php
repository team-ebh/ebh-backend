<?php

declare(strict_types=1);

namespace App\Actions\Filament\Rider;

use App\Exceptions\Rider\RiderHasActiveTripException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;

/**
 * Validate Rider Status Change Action
 *
 * Checks if a rider can be disabled
 * Throws exception if rider has active trip
 */
readonly class ValidateRiderStatusChangeAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * @throws RiderHasActiveTripException
     */
    public function __invoke(int $riderId, bool $currentEnabled, bool $newEnabled): void
    {
        // Check if changing from enabled to disabled
        $isDisabling = $currentEnabled === true && $newEnabled === false;

        if (! $isDisabling) {
            return;
        }

        // Check for active trip
        if ($this->riderTripRepository->existsActiveTrip($riderId)) {
            throw new RiderHasActiveTripException();
        }
    }
}
