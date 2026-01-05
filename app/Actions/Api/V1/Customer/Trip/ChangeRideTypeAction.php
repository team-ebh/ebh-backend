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
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\UpdateTripPricesPipe;
use App\Pipelines\Api\V1\Customer\Trip\ChangeRideType\ValidateTripPipe;
use Illuminate\Pipeline\Pipeline;

/**
 * Change Ride Type Action
 *
 * Updates trip ride type, scheduled time, and recalculates pricing
 * Used to change ride type and update trip pricing
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
                ValidateTripPipe::class,
                CalculateDistancePipe::class,
                CalculateRidePricingPipe::class,
                BuildRideTypeBreakdownPipe::class,
                UpdateTripPricesPipe::class,
            ])
            ->thenReturn();

        return [
            'price_breakdown' => $result->priceBreakdown,
            'price_estimation' => $result->priceEstimation,
            'waiting_time_config' => $result->waitingTimeConfig,
        ];
    }
}
