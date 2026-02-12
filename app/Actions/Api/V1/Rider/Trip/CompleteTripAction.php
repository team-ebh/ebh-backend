<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\CompleteTripDTO;
use App\Models\Trip;
use App\Pipelines\Rider\Trip\CompleteTrip\FinalizeAndCalculatePipe;
use App\Pipelines\Rider\Trip\CompleteTrip\UpdateStatusPipe;
use App\Pipelines\Rider\Trip\CompleteTrip\ValidateAndLoadPipe;
use App\Services\Cache\AppStateCache;
use App\Services\Cache\RiderCache;
use Illuminate\Pipeline\Pipeline;

/**
 * Complete Trip Action
 *
 * Handles rider completing a trip location (origin or destination)
 */
readonly class CompleteTripAction
{
    /**
     * Execute the action
     *
     * @return array{next_action: string|null, trip_completed: bool}
     *
     * @throws \Throwable
     */
    public function __invoke(CompleteTripDTO $dto): array
    {
        $result = safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'markAsCompleted'], $dto);

        // Clear cache for both rider and customer
        $customerId = $dto->tripRequest->trip->{Trip::COLUMN_CUSTOMER_ID} ?? null;

        AppStateCache::forgetBoth($customerId, $dto->riderId);
        RiderCache::forgetRider($dto->riderId);

        return $result;
    }

    /**
     * Mark location as completed using Pipeline pattern
     *
     * @return array{next_action: string|null, trip_completed: bool}
     *
     * @throws \Throwable
     */
    public function markAsCompleted(CompleteTripDTO $dto): array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                ValidateAndLoadPipe::class,
                UpdateStatusPipe::class,
                FinalizeAndCalculatePipe::class,
            ])
            ->thenReturn();

        return [
            'next_action' => $result['next_action'],
            'trip_completed' => $result['trip_completed'],
        ];
    }
}
