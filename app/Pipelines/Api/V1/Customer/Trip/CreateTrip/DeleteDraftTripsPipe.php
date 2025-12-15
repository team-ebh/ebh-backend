<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Delete Draft Trips Pipe
 *
 * Deletes all existing draft trips for the customer before creating a new trip
 */
readonly class DeleteDraftTripsPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        // Delete all draft trips for this customer
        $this->tripRepository->deleteDraftTrips($context->dto->customerId);

        return $next($context);
    }
}
