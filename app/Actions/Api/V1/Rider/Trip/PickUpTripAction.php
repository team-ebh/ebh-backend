<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\PickUpTripDTO;
use App\Models\Trip;
use App\Pipelines\Rider\Trip\PickUpTrip\CalculateWaitingTimePipe;
use App\Pipelines\Rider\Trip\PickUpTrip\ExecuteAndBroadcastPipe;
use App\Pipelines\Rider\Trip\PickUpTrip\ValidateAndLoadPipe;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\TripCache;
use Illuminate\Pipeline\Pipeline;

/**
 * Pick Up Trip Action
 *
 * Handles rider picking up passenger at trip location
 */
readonly class PickUpTripAction
{
    /**
     * Execute the action
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function __invoke(PickUpTripDTO $dto): array
    {
        $result = safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsPickedUp'], $dto);

        // Clear cache for both rider and customer
        $customerId = $dto->tripRequest->trip->{Trip::COLUMN_CUSTOMER_ID} ?? null;

        AppStateCache::forgetBoth($customerId, $dto->riderId);
        TripCache::forgetBoth($customerId, $dto->riderId);

        return $result;
    }

    /**
     * Mark location as picked up using Pipeline pattern
     *
     * @return array{next_action: string|null}
     *
     * @throws \Throwable
     */
    public function markAsPickedUp(PickUpTripDTO $dto): array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                ValidateAndLoadPipe::class,
                ExecuteAndBroadcastPipe::class,
                CalculateWaitingTimePipe::class, // Calculate waiting time for ROUND_TRIP_WAIT second pickup
            ])
            ->thenReturn();

        return [
            'next_action' => $result['next_action'],
        ];
    }
}
