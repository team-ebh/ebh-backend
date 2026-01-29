<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Earnings;

use App\Http\Resources\Api\InfiniteScrollPaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Earnings Latest Trips Resource
 *
 * Returns paginated list of recent trips with earnings data
 */
class EarningsLatestTripsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * List of trips with earnings
             *
             * @var EarningsTripResource[]
             */
            'trips' => EarningsTripResource::collection($this->resource['data']),

            /**
             * Pagination information
             *
             * @var InfiniteScrollPaginationResource
             */
            'pagination' => new InfiniteScrollPaginationResource($this->resource['pagination']),
        ];
    }
}
