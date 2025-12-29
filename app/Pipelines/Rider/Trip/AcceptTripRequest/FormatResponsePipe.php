<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\AcceptTripRequest;

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
        $dto = $payload['dto'];
        $trip = $payload['trip'];

        // Prepare formatted response data
        $tripData = $this->tripDataFormatter->prepareTripData($dto->tripRequest);

        // Add next action information
        $nextAction = $this->tripActionService->getNextAction($trip->fresh());

        $payload['result'] = array_merge($tripData, [
            'next_action' => $nextAction,
            'trip_completed' => $nextAction === null,
            'customer' => $trip->customer,
            'trip_type_id' => $trip->trip_type_id,
            'ride_type' => $trip->ride_type,
        ]);

        return $next($payload);
    }
}
