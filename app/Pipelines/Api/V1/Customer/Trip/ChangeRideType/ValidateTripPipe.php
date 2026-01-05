<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ChangeRideType;

use App\Exceptions\Customer\TripNotBelongToCustomerException;
use App\Exceptions\Trip\TripNotDraftException;
use Closure;

/**
 * Validate Trip Pipe
 *
 * Validates trip ownership and status before changing ride type
 */
readonly class ValidateTripPipe
{
    /**
     * @throws TripNotBelongToCustomerException
     * @throws TripNotDraftException
     * @throws \Throwable
     */
    public function handle(ChangeRideTypeContext $context, Closure $next): mixed
    {
        // Validate trip belongs to authenticated customer
        throw_if(
            ! $context->dto->trip->belongsToCustomer($context->dto->customerId),
            TripNotBelongToCustomerException::class
        );

        // Validate trip is in DRAFT status
        throw_if(
            ! $context->dto->trip->isDraft(),
            TripNotDraftException::class
        );

        // Load accessibility requirements for the trip
        $context->dto->trip->load('accessibility');

        return $next($context);
    }
}
