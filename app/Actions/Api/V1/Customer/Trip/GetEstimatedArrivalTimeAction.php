<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\GetEstimatedArrivalTimeDTO;
use App\Pipelines\Customer\Trip\GetEstimatedArrivalTime\ValidateAndLoadPipe;
use App\Pipelines\Shared\Trip\GetEstimatedArrivalTime\CalculateEstimatedTimePipe;
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
