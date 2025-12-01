<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\CancelTripDTO;
use App\Models\Trip;
use App\Pipelines\Rider\Trip\CancelTrip\ExecuteAndBroadcastPipe;
use App\Pipelines\Rider\Trip\CancelTrip\ValidatePipe;
use Illuminate\Pipeline\Pipeline;

readonly class CancelTripAction
{
    /**
     * Cancel a trip by the rider
     *
     * @throws \Throwable
     */
    public function __invoke(CancelTripDTO $dto): Trip
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'cancelTrip'], $dto);
    }

    /**
     * Cancel the trip using Pipeline pattern
     *
     * @throws \Throwable
     */
    public function cancelTrip(CancelTripDTO $dto): Trip
    {
        $result = app(Pipeline::class)
            ->send(['dto' => $dto])
            ->through([
                ValidatePipe::class,
                ExecuteAndBroadcastPipe::class,
            ])
            ->thenReturn();

        return $result['trip'];
    }
}
