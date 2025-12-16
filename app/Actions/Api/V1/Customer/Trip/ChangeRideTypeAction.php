<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\ChangeRideTypeDTO;
use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\TripNotDraftException;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\BuildRideTypeBreakdownPipe;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\CalculateDistancePipe;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\CalculateRidePricingPipe;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\ChangeRideTypeContext;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\UpdateDestinationLocationPipe;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\UpdateTripPricesPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Change Ride Type Action
 *
 * Calculates pricing based on ride type and updates trip prices using pipeline pattern
 */
readonly class ChangeRideTypeAction
{
    /**
     * Execute the action
     *
     * @throws TripNotBelongToCustomerException
     * @throws TripNotDraftException
     * @throws \Throwable
     */
    public function __invoke(ChangeRideTypeDTO $dto): array
    {
        // Validate trip belongs to authenticated customer
        throw_if(
            ! $dto->trip->belongsToCustomer($dto->customerId),
            TripNotBelongToCustomerException::class
        );

        throw_if(! $dto->trip->isDraft(), TripNotDraftException::class);

        // Load accessibility requirements for the trip
        $dto->trip->load('accessibility');

        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do([$this, 'calculateAndUpdateRidePrice'], $dto);
    }

    /**
     * Calculate ride price and update trip using pipeline pattern
     *
     * @throws \Throwable
     */
    public function calculateAndUpdateRidePrice(ChangeRideTypeDTO $dto): array
    {
        $context = new ChangeRideTypeContext($dto);

        /** @var ChangeRideTypeContext $result */
        $result = app(Pipeline::class)
            ->send($context)
            ->through([
                CalculateDistancePipe::class,
                CalculateRidePricingPipe::class,
                BuildRideTypeBreakdownPipe::class,
                UpdateTripPricesPipe::class,
                UpdateDestinationLocationPipe::class,
            ])
            ->thenReturn();

        return [
            'price_breakdown' => $result->priceBreakdown,
            'price_estimation' => $result->priceEstimation,
            'waiting_time_config' => $result->waitingTimeConfig,
        ];
    }
}
