<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\AcceptTripRequestDTO;
use App\Models\Trip;
use App\Pipelines\Rider\Trip\AcceptTripRequest\ExecuteAndBroadcastPipe;
use App\Pipelines\Rider\Trip\AcceptTripRequest\FormatResponsePipe;
use App\Pipelines\Rider\Trip\AcceptTripRequest\LoadAndValidatePipe;
use App\Services\Cache\AppStateCache;
use Illuminate\Pipeline\Pipeline;

/**
 * Accept Trip Request Action
 *
 * Handles rider accepting a trip request
 */
readonly class AcceptTripRequestAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(AcceptTripRequestDTO $dto): array
    {
        $result = safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'acceptTrip'], $dto);

        // Clear cache for both rider and customer
        AppStateCache::forgetBoth(
            $dto->tripRequest->trip->{Trip::COLUMN_CUSTOMER_ID},
            $dto->riderId
        );

        return $result;
    }

    /**
     * Accept the trip request using Pipeline pattern
     *
     * @throws \Throwable
     */
    public function acceptTrip(AcceptTripRequestDTO $dto): array
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                LoadAndValidatePipe::class,
                ExecuteAndBroadcastPipe::class,
                FormatResponsePipe::class,
            ])
            ->thenReturn();

        return $result['result'];
    }
}
