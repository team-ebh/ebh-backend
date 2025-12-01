<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\Models\Trip;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildArrivedTimePipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildLocationHistoryPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildRiderDataPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\BuildVehicleDataPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\CheckTripStatusPipe;
use App\Pipelines\Api\V1\Customer\Trip\GetTripStatus\TripStatusContext;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Trip Status Action
 *
 * Returns comprehensive trip status information including rider, vehicle, and location data using pipeline pattern
 */
readonly class GetTripStatusAction
{
    /**
     * Execute the action
     *
     * Returns trip status with rider, vehicle, and location information
     */
    public function __invoke(Trip $trip): array
    {
        return $this->buildTripStatus($trip);
    }

    /**
     * Build trip status using pipeline pattern
     */
    private function buildTripStatus(Trip $trip): array
    {
        $context = new TripStatusContext($trip);

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

        return [
            'found' => $result->found,
            'id' => $result->trip->{Trip::COLUMN_ID},
            'status' => $result->trip->{Trip::COLUMN_STATUS},
            'arrived_time' => $result->arrivedTime,
            'rider' => $result->rider,
            'vehicle' => $result->vehicle,
            'map_locations' => $result->mapLocations,
            'formatted_locations' => $result->formattedLocations,
        ];
    }
}
