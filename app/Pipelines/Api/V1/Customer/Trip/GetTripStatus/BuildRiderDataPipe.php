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
            'image' => 'https://fls-a00e2417-bd4b-4518-8f0a-2f1c385db629.367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com/16/01KA41F1M1NF4RA3JDRHVFFR7E.jpg?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=b11b87be32b3f4da84f322a21f7695d1%2F20251117%2Fauto%2Fs3%2Faws4_request&X-Amz-Date=20251117T111444Z&X-Amz-SignedHeaders=host&X-Amz-Expires=2715&X-Amz-Signature=27146fcc910953d61e95fc8d98275136724bf2aaba5a133476d748cc49045e9e',
            'name' => 'Ahmed Al-Mansour',
            'phone_number' => '50123456',
            'rating' => 4.8,
            'accessibility_certifications' => ['wheelchair_accessible', 'hearing_assistance'],
        ];

        return $next($context);
    }
}
