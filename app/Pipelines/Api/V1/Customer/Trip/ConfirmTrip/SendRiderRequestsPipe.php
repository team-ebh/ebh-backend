<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Services\Trip\TripRequestService;
use Closure;

/**
 * Send Rider Requests Pipe
 *
 * Sends trip requests to eligible riders
 */
readonly class SendRiderRequestsPipe
{
    public function __construct(
        private TripRequestService $tripRequestService
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Send requests to eligible riders (first attempt with 200m radius)
        $this->tripRequestService->sendRequestsToRiders($context->dto->trip, searchAttempt: 1);

        return $next($context);
    }
}
