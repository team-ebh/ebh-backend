<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripStatusCannotBeCheckedException;
use App\Models\Trip;
use Closure;

/**
 * Check Trip Status Pipe
 *
 * Determines if trip status information is available
 */
class CheckTripStatusPipe
{
    /**
     * Statuses that cannot be checked
     */
    private const array INVALID_STATUSES = [
        TripStatusEnum::DRAFT,
        TripStatusEnum::CANCELED_BY_CUSTOMER,
        TripStatusEnum::CANCELLED_BY_RIDER,
        TripStatusEnum::COMPLETED,
    ];

    /**
     * @throws TripStatusCannotBeCheckedException
     * @throws \Throwable
     */
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        throw_if(
            in_array($context->trip->{Trip::COLUMN_STATUS}, self::INVALID_STATUSES, true),
            TripStatusCannotBeCheckedException::class,
        );

        $context->found = $context->trip->hasTripStatusAvailable();

        return $next($context);
    }
}
