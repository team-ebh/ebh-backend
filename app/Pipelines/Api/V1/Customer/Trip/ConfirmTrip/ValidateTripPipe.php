<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\CustomerHasUnpaidTripException;
use App\Exceptions\Trip\RideTypeMismatchException;
use App\Exceptions\Trip\TripNotPendingException;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use App\Models\Trip;
use Closure;

/**
 * Validate Trip Pipe
 *
 * Validates trip ownership, status, and customer payment status
 */
readonly class ValidateTripPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    /**
     * @throws TripNotBelongToCustomerException
     * @throws TripNotPendingException
     * @throws RideTypeMismatchException
     * @throws CustomerHasUnpaidTripException|\Throwable
     */
    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Validate trip belongs to authenticated customer
        throw_if(
            ! $context->dto->trip->belongsToCustomer($context->dto->customerId),
            TripNotBelongToCustomerException::class
        );

        // Validate trip is in DRAFT status
        throw_if(
            ! $context->dto->trip->isDraft(),
            TripNotPendingException::class
        );

        // Validate ride type matches trip's ride type
        throw_if(
            $context->dto->rideTypeId !== $context->dto->trip->{Trip::COLUMN_RIDE_TYPE},
            RideTypeMismatchException::class
        );

        // Check if customer has unpaid trips
        $lastTrip = $this->tripRepository->getLastTrip($context->dto->customerId);
        throw_if(
            $lastTrip && ! $lastTrip->hasCompletedAndPaidPayment(),
            CustomerHasUnpaidTripException::class
        );

        return $next($context);
    }
}
