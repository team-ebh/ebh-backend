<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Http\Resources\Api\InfiniteScrollPaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Upcoming Trip List Resource
 *
 * Formats trip data for upcoming trips list (scheduled trips waiting for processing)
 */
class HistoryUpcomingTripListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip History list
             *
             * @var UpcomingTripListResource[]
             */
            'trips' => UpcomingTripListResource::collection($this->resource['data']),

            /**
             * Pagination
             *
             * @var InfiniteScrollPaginationResource
             */
            'pagination' => new InfiniteScrollPaginationResource($this->resource['pagination']),
        ];
    }
}
