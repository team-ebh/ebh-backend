<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Earnings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Earnings Filters Resource
 *
 * Returns available earnings filter options
 */
class EarningsFiltersResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Filter tabs with labels and default selection
             *
             * @var array<int, array{id: string, label: string, is_default: bool}>
             */
            'filters' => $this->resource,
        ];
    }
}
