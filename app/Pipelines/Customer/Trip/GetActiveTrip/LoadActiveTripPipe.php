<?php

declare(strict_types=1);

namespace App\Pipelines\Customer\Trip\GetActiveTrip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\CustomerTripRepositoryInterface;
use Closure;

readonly class LoadActiveTripPipe
{
    public function __construct(
        private CustomerTripRepositoryInterface $customerTripRepository,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];

        // Get active trip for customer
        $activeTrip = $this->customerTripRepository->getActiveTrip($dto->customerId);

        if (! $activeTrip) {
            $payload['result'] = null;

            return $payload;
        }

        $payload['activeTrip'] = $activeTrip;

        return $next($payload);
    }
}
