<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History\Concerns;

use App\Models\Rider;
use App\Models\Trip;

/**
 * Trait for formatting rider data consistently across resources
 */
trait FormatsRiderData
{
    /**
     * Format rider data for RiderInfoResource
     */
    protected function formatRiderData(Trip $trip): array
    {
        $rider = $trip->rider;

        return [
            'id' => $rider->id,
            'image' => $rider->getFirstMediaLink(),
            'name' => $rider->{Rider::COLUMN_FULL_NAME},
            'phone_number' => $rider->{Rider::COLUMN_PHONE_NUMBER},
            'rating' => (float) ($rider->rating ?? 0),
            'accessibility_certifications' => $rider->accessibilityCertifications ?? collect(),
        ];
    }
}
