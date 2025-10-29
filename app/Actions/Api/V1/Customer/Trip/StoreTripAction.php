<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Models\Trip;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\AttachAccessibilityRequirementsPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CalculatePricingPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CreateTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeDestinationPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeOriginPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\TripCreationContext;
use App\Services\PriceBreakdownService;
use Illuminate\Pipeline\Pipeline;

/**
 * Store Trip Action
 *
 * Creates a new trip using a pipeline pattern
 */
class StoreTripAction
{
    public function __construct(
        private readonly PriceBreakdownService $priceBreakdownService
    ) {}

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
                ReverseGeocodeOriginPipe::class,
                ReverseGeocodeDestinationPipe::class,
                CalculatePricingPipe::class,
                CreateTripPipe::class,
                AttachAccessibilityRequirementsPipe::class,
            ])
            ->thenReturn();

        // Load relationships for response
        $result->trip->load(['customer', 'accessibility', 'locations']);

        // Build price breakdown using service
        $priceBreakdown = $this->priceBreakdownService->buildTripStoreBreakdown(
            $result->baseFare,
            $dto->accessibilityRequirements,
            $result->accessibilityCost
        );

        // Build price estimation using service
        $priceEstimation = $this->priceBreakdownService->buildPriceEstimation($result->estimatedPrice);

        return [
            'trip' => $result->trip,
            'dto' => $dto,
            'price_breakdown' => $priceBreakdown,
            'price_estimation' => $priceEstimation,
        ];
    }
}
