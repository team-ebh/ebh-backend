<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Enums\Trip\TripHistoryFilterEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Filters Resource
 *
 * Returns available trip history filters with rider statistics
 */
class FiltersResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Filter tabs with status labels and default selection
             *
             * @var array<int, array{id: string, label: string, is_default: bool}>
             */
            'filters' => TripHistoryFilterEnum::getFiltersArray(),

            /**
             * Rider's total completed rides count
             *
             * @example 150
             *
             * @var int
             */
            'total_rides_count' => $this->resource['total_rides_count'],

            /**
             * Rider's total canceled trips count
             *
             * @example 5
             *
             * @var int
             */
            'canceled_count' => $this->resource['canceled_count'],
        ];
    }
}
