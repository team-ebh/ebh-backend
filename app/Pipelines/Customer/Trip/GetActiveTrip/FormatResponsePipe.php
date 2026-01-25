<?php

declare(strict_types=1);

namespace App\Pipelines\Customer\Trip\GetActiveTrip;

use App\Models\Trip;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildArrivedTimePipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildLocationHistoryPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildRiderDataPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildVehicleDataPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\CheckTripStatusPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\TripStatusContext;
use App\Services\Trip\TripRequestFormatterService;
use Closure;
use Illuminate\Pipeline\Pipeline;

readonly class FormatResponsePipe
{
    public function __construct(
        private TripRequestFormatterService $tripRequestFormatter,
    ) {}

    /**
     * Handle the pipeline
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $activeTrip = $payload['activeTrip'];

        // Build trip status using existing GetTripStatus pipelines
        $context = new TripStatusContext($activeTrip);

        /** @var TripStatusContext $result */
        $result = app(Pipeline::class)
            ->send($context)
            ->through([
                CheckTripStatusPipe::class,
                BuildArrivedTimePipe::class,
                BuildRiderDataPipe::class,
                BuildVehicleDataPipe::class,
                BuildLocationHistoryPipe::class,
            ])
            ->thenReturn();

        // Prepare response (same as TripStatusResource)
        $payload['result'] = [
            'found' => $result->found,
            'id' => $result->trip->{Trip::COLUMN_ID},
            'status' => $result->trip->{Trip::COLUMN_STATUS},
            'arrived_time' => $result->arrivedTime,
            'rider' => $result->rider,
            'vehicle' => $result->vehicle,
            'map_locations' => $result->mapLocations,
            'formatted_locations' => $result->formattedLocations,
            'payment' => $this->tripRequestFormatter->preparePaymentData($result->trip),
        ];

        return $next($payload);
    }
}
