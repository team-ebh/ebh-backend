<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

use App\Models\Rider;
use Closure;

/**
 * Build Rider Data Pipe
 *
 * Builds rider information for the response
 */
class BuildRiderDataPipe
{
    public function handle(TripStatusContext $context, Closure $next): mixed
    {
        if (! $context->found) {
            return $next($context);
        }

        $rider = $context->trip->rider;

        if (! $rider) {
            $context->rider = null;

            return $next($context);
        }

        // Load accessibility certifications relationship
        $rider->loadMissing('accessibilityCertifications');

        $context->rider = [
            'id' => $rider->{Rider::COLUMN_ID},
            'image' => $rider->getFirstMediaLink(),
            'name' => $rider->{Rider::COLUMN_FULL_NAME},
            'phone_number' => $rider->{Rider::COLUMN_PHONE_NUMBER},
            'rating' => 4.8, // TODO: Implement actual rating calculation
            'accessibility_certifications' => $rider->accessibilityCertifications,
        ];

        return $next($context);
    }
}
