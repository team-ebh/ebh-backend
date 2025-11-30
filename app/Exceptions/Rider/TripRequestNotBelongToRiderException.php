<?php

declare(strict_types=1);

namespace App\Exceptions\Rider;

use App\Exceptions\BaseException;

/**
 * Trip Request Not Belong To Rider Exception
 *
 * Thrown when a rider tries to accept/decline a trip request not assigned to them
 */
class TripRequestNotBelongToRiderException extends BaseException
{
    public function message(): string
    {
        return trans('trips.trip_request_not_belong_to_rider');
    }

    public function statusCode(): int
    {
        return 403;
    }
}
