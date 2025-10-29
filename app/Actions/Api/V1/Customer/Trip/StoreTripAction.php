<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\TripStoreDTO;
use App\Enums\Currency\CurrencyEnum;
use App\Models\Trip;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\AttachAccessibilityRequirementsPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CalculatePricingPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\CreateTripPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeDestinationPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\ReverseGeocodeOriginPipe;
use App\Pipelines\Api\V1\Customer\Trip\CreateTrip\TripCreationContext;
use Illuminate\Pipeline\Pipeline;

/**
 * Store Trip Action
 *
 * Creates a new trip using a pipeline pattern
 */
class StoreTripAction
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
                ReverseGeocodeOriginPipe::class,
                ReverseGeocodeDestinationPipe::class,
                CalculatePricingPipe::class,
                CreateTripPipe::class,
                AttachAccessibilityRequirementsPipe::class,
            ])
            ->thenReturn();

        // Load relationships for response
        $result->trip->load(['customer', 'accessibility']);

        $priceBreakdown = [
            [
                'label' => 'Base Fare',
                'value' => priceFormat($result->baseFare) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];

        // Add accessibility cost if any accessibility requirements exist
        if ($dto->accessibilityRequirements) {
            // Get translated names of selected accessibility requirements
            $accessibilityNames = array_map(
                fn ($requirement) => $requirement->getLabel(),
                $dto->accessibilityRequirements
            );
            $subLabel = implode(', ', $accessibilityNames);

            if ($result->accessibilityCost > 0) {
                $priceBreakdown[] = [
                    'label' => 'Accessibility Services',
                    'sub_label' => $subLabel,
                    'value' => priceFormat($result->accessibilityCost) . ' ' . CurrencyEnum::KWD->getLabel(),
                ];
            } else {
                // If there are accessibility requirements but cost is 0 (all are free/included)
                $priceBreakdown[] = [
                    'label' => 'Accessibility Services',
                    'sub_label' => $subLabel,
                    'value' => 'Included',
                ];
            }
        }

        return [
            'trip' => $result->trip,
            'dto' => $dto,
            'price_breakdown' => $priceBreakdown,
            'price_estimation' => [
                'label' => 'Price estimation',
                'value' => priceFormat($result->estimatedPrice) . ' ' . CurrencyEnum::KWD->getLabel(),
            ],
        ];
    }
}
