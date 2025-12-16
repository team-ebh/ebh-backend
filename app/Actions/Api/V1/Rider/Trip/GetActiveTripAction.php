<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetActiveTripDTO;
use App\Pipelines\Rider\Trip\GetActiveTrip\BuildArrivedTimePipe;
use App\Pipelines\Rider\Trip\GetActiveTrip\FormatResponsePipe;
use App\Pipelines\Rider\Trip\GetActiveTrip\LoadActiveTripPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Active Trip Action
 *
 * Handles retrieving rider's active trip information
 */
readonly class GetActiveTripAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(GetActiveTripDTO $dto): ?array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                LoadActiveTripPipe::class,
                BuildArrivedTimePipe::class,
                FormatResponsePipe::class,
            ])
            ->thenReturn();

        return $result['result'] ?? null;
    }
}
