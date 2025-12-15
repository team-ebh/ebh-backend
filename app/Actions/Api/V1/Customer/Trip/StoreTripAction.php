<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\AttachAccessibilityRequirementsPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\BuildPriceBreakdownPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CalculatePricingPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CheckActiveTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CreateTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\DeleteDraftTripsPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeDestinationPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeOriginPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\TripCreationContext;
use Illuminate\Pipeline\Pipeline;

/**
 * Store Trip Action
 *
 * Creates a new trip using a pipeline pattern
 */
readonly class StoreTripAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(TripStoreDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'storeTrip'], $dto);
    }

    /**
     * Store the trip using pipeline pattern
     *
     * @throws \Throwable
     */
    public function storeTrip(TripStoreDTO $dto): array
    {
        $context = new TripCreationContext($dto);

        /** @var TripCreationContext $result */
        $result = app(Pipeline::class)
            ->send($context)
            ->through([
                DeleteDraftTripsPipe::class,
                CheckActiveTripPipe::class,
                ReverseGeocodeOriginPipe::class,
                ReverseGeocodeDestinationPipe::class,
                CalculatePricingPipe::class,
                CreateTripPipe::class,
                AttachAccessibilityRequirementsPipe::class,
                BuildPriceBreakdownPipe::class,
            ])
            ->thenReturn();

        return [
            'trip' => $result->trip,
            'dto' => $dto,
            'price_breakdown' => $result->priceBreakdown,
            'price_estimation' => $result->priceEstimation,
        ];
    }
}
