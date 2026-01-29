<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Http\Resources\Api\InfiniteScrollPaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * History Past Trip List Resource
 *
 * Formats trip data for past trips list (completed and canceled trips)
 */
class HistoryPastTripListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip History list
             *
             * @var PastTripListResource[]
             */
            'trips' => PastTripListResource::collection($this->resource['data']),

            /**
             * Pagination
             *
             * @var InfiniteScrollPaginationResource
             */
            'pagination' => new InfiniteScrollPaginationResource($this->resource['pagination']),
        ];
    }
}
