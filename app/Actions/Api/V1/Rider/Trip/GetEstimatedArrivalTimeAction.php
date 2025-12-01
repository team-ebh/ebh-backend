<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetEstimatedArrivalTimeDTO;
use App\Pipelines\Rider\Trip\GetEstimatedArrivalTime\CalculateEstimatedTimePipe;
use App\Pipelines\Rider\Trip\GetEstimatedArrivalTime\ValidateAndLoadPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Estimated Arrival Time Action
 *
 * Calculates the estimated arrival time from rider's current location to the next destination
 */
readonly class GetEstimatedArrivalTimeAction
{
    /**
     * Execute the action
     *
     * @return array{estimated_arrival_seconds: int}
     *
     * @throws \Throwable
     */
    public function __invoke(GetEstimatedArrivalTimeDTO $dto): array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                ValidateAndLoadPipe::class,
                CalculateEstimatedTimePipe::class,
            ])
            ->thenReturn();

        return $result['result'];
    }
}
