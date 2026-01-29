<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Earnings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Earnings Report Resource
 *
 * Returns earnings summary based on selected filter
 */
class EarningsReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Total earnings amount (after commission deduction)
             *
             * @example 165.50
             *
             * @var float
             */
            'total_earnings' => $this->resource['total_earnings'],

            /**
             * Currency code
             *
             * @example "KWD"
             *
             * @var string
             */
            'currency' => $this->resource['currency'],

            /**
             * Percentage change from previous period
             *
             * @example 12
             *
             * @var int
             */
            'change_percentage' => $this->resource['change_percentage'],

            /**
             * Direction of change: up, down, or same
             *
             * @example "up"
             *
             * @var string
             */
            'change_direction' => $this->resource['change_direction'],

            /**
             * Human-readable comparison text
             *
             * @example "+12% from last week"
             *
             * @var string
             */
            'comparison_text' => $this->resource['comparison_text'],

            /**
             * Total number of completed rides
             *
             * @example 15
             *
             * @var int
             */
            'total_rides' => $this->resource['total_rides'],

            /**
             * Total minutes worked
             *
             * @example 125
             *
             * @var int
             */
            'total_minutes' => $this->resource['total_minutes'],

            /**
             * Average earning per ride
             *
             * @example 9.50
             *
             * @var float
             */
            'average_per_ride' => $this->resource['average_per_ride'],

            /**
             * Whether the rider has no earnings data for this period
             *
             * @example false
             *
             * @var bool
             */
            'is_empty' => $this->resource['is_empty'],
        ];
    }
}
