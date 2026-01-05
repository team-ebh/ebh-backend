<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\GetActiveTrip;

use App\Services\Trip\TripActionService;
use App\Services\Trip\TripDataFormatterService;
use Closure;

readonly class FormatResponsePipe
{
    public function __construct(
        private TripDataFormatterService $tripDataFormatter,
        private TripActionService $tripActionService,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $activeTrip = $payload['activeTrip'];
        $tripRequest = $payload['tripRequest'];

        // Prepare base trip data
        $tripData = $this->tripDataFormatter->prepareTripData($tripRequest);

        // Add next action information
        $nextAction = $this->tripActionService->getNextAction($activeTrip->fresh());

        $payload['result'] = array_merge($tripData, [
            'next_action' => $nextAction?->value,
            'trip_completed' => $nextAction === null,
            'customer' => $activeTrip->customer,
            'trip_type_id' => $activeTrip->trip_type_id,
            'ride_type' => $activeTrip->ride_type,
        ]);

        return $next($payload);
    }
}
