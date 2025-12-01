<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\ArrivedTripDTO;
use App\Pipelines\Rider\Trip\ArriveTrip\ExecuteAndBroadcastPipe;
use App\Pipelines\Rider\Trip\ArriveTrip\ValidateAndLoadPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Arrived Trip Action
 *
 * Handles rider arriving at a trip location
 */
readonly class ArriveTripAction
{
    /**
     * Execute the action
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function __invoke(ArrivedTripDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsArrived'], $dto);
    }

    /**
     * Mark location as arrived using Pipeline pattern
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function markAsArrived(ArrivedTripDTO $dto): array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                ValidateAndLoadPipe::class,
                ExecuteAndBroadcastPipe::class,
            ])
            ->thenReturn();

        return [
            'next_action' => $result['next_action'],
        ];
    }
}
