<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Services\Trip\TripLocationFormatterService;
use Closure;

/**
 * Build Location History Pipe
 *
 * Builds location history from trip locations with sequences
 */
readonly class BuildLocationHistoryPipe
{
    public function __construct(
        private TripLocationFormatterService $locationFormatter
    ) {}

    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        // Load trip locations if not already loaded
        if (! $context->trip->relationLoaded('locations')) {
            $context->trip->load(['locations' => fn ($q) => $q->select([
                'id',
                'trip_id',
                'type',
                'status',
                'location_title',
                'location_sub_title',
                'latitude',
                'longitude',
                'sequence',
            ])]);
        }

        // Build map locations using service
        $context->mapLocations = $this->locationFormatter
            ->prepareMapLocations($context->trip)
            ->toArray();

        // Build formatted locations using service
        $context->formattedLocations = $this->locationFormatter
            ->prepareFormattedLocations($context->trip)
            ->toArray();

        return $next($context);
    }
}
