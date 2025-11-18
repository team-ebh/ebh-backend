<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetTripRequestsDTO;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use App\Models\TripRequest;
use App\Services\Trip\TripDataFormatterService;
use Illuminate\Support\Collection;

/**
 * Get Trip Requests Action
 *
 * Handles retrieving available trip requests for a rider
 */
readonly class GetTripRequestsAction
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
        private TripDataFormatterService $tripDataFormatter,
    ) {}

    /**
     * Execute the action
     *
     * @return Collection<int, array>
     *
     * @throws \Throwable
     */
    public function __invoke(GetTripRequestsDTO $dto): Collection
    {
        $trips = $this->riderTripRepository->getTripRequests($dto->riderId);

        return $trips->map(fn (TripRequest $tripRequest) => $this->tripDataFormatter->prepareTripData($tripRequest));
    }
}
