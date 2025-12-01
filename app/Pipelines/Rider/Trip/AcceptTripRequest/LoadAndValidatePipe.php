<?php

declare(strict_types=1);

namespace App\Pipelines\Rider\Trip\AcceptTripRequest;

use App\Exceptions\Rider\RiderNotAvailableException;
use App\Exceptions\Rider\TripNotAvailableException;
use App\Exceptions\Rider\TripRequestNotBelongToRiderException;
use App\Interfaces\Repositories\Api\V1\Rider\Trip\RiderTripRepositoryInterface;
use Closure;

readonly class LoadAndValidatePipe
{
    public function __construct(
        private RiderTripRepositoryInterface $riderTripRepository,
    ) {}

    /**
     * Handle the pipeline
     *
     * @throws \Throwable
     */
    public function handle(array $payload, Closure $next): mixed
    {
        $dto = $payload['dto'];

        // Get rider from repository
        $rider = $this->riderTripRepository->getRider($dto->riderId);
        $payload['rider'] = $rider;

        // Verify rider is online and available
        throw_if(
            ! $rider->isOnline(),
            RiderNotAvailableException::class
        );

        // Verify trip request belongs to this rider
        throw_if(
            ! $dto->tripRequest->belongsToRider($dto->riderId),
            TripRequestNotBelongToRiderException::class
        );

        // Verify trip request is in pending status
        throw_if(
            ! $dto->tripRequest->isPending(),
            TripNotAvailableException::class
        );

        // Verify trip is in pending rider status
        throw_if(
            ! $dto->tripRequest->trip->isPendingRider(),
            TripNotAvailableException::class
        );

        return $next($payload);
    }
}
