<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Receipt Link Resource
 */
class TripReceiptLinkResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Temporary signed URL to download trip receipt (valid for 10 minutes)
             *
             * @example "https://api.ebh.com/v1/trip-history/123/download-receipt?expires=1234567890&signature=abc123..."
             *
             * @var string
             */
            'link' => $this->resource['link'],
        ];
    }
}
