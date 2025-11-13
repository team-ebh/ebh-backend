<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\GetTripStatus;

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

        // TODO: Replace with actual rider data from database

        $context->rider = [
            'id' => 1,
            'name' => 'Ahmed Al-Mansour',
            'phone_number' => '50123456',
            'rating' => 4.8,
            'accessibility_certifications' => ['wheelchair_accessible', 'hearing_assistance'],
        ];

        return $next($context);
    }
}
