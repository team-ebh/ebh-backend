<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\GetActiveTripDTO;
use App\Pipelines\Customer\Trip\GetActiveTrip\FormatResponsePipe;
use App\Pipelines\Customer\Trip\GetActiveTrip\LoadActiveTripPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Get Active Trip Action
 *
 * Handles retrieving customer's active trip information
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
                FormatResponsePipe::class,
            ])
            ->thenReturn();

        return $result['result'] ?? null;
    }
}
