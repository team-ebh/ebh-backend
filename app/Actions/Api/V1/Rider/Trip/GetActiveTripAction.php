<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetActiveTripDTO;
use App\Pipelines\Rider\Trip\GetActiveTrip\FormatResponsePipe;
use App\Pipelines\Rider\Trip\GetActiveTrip\LoadActiveTripPipe;
use App\Services\Cache\TripCache;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Active Trip Action
 *
 * Handles retrieving rider's active trip information
 * Caches active trip data for rider
 */
readonly class GetActiveTripAction
{
    /**
     * Execute the action
     * Caches active trip data for rider
     *
     * @throws \Throwable
     */
    public function __invoke(GetActiveTripDTO $dto): ?array
    {
        return TripCache::rider($dto->riderId, function () use ($dto) {
            $result = app(Pipeline::class)
                ->send(['dto' => $dto])
                ->through([
                    LoadActiveTripPipe::class,
                    FormatResponsePipe::class,
                ])
                ->thenReturn();

            return $result['result'] ?? null;
        });
    }
}
