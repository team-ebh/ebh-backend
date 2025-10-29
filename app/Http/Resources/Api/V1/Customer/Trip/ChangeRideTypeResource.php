<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Change Ride Type Resource
 *
 * Formats ride type pricing calculation for API responses
 */
class ChangeRideTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Price Breakdown
             *
             * Array of price breakdown items showing fare components
             *
             * @example [{"label": "Base Fare", "sub_label": "One Way", "value": "2.500 KWD"}]
             *
             * @var array<TripPaymentResource>
             */
            'price_breakdown' => TripPaymentResource::collection($this->resource['price_breakdown']),

            /**
             * Price Estimation
             *
             * Total estimated price for the trip (null if location data not provided)
             *
             * @example {"label": "Price estimation", "value": "5.000 KWD"}
             *
             * @var TripPaymentResource|null
             */
            'price_estimation' => $this->resource['price_estimation'] ? new TripPaymentResource($this->resource['price_estimation']) : null,

            /**
             * Waiting Time Configuration
             *
             * Configuration for waiting time pricing (only for ROUND_TRIP_WAIT ride type)
             *
             * @example {"price": "2.500 KWD", "time": 30}
             *
             * @var array|null
             */
            'waiting_time_config' => $this->when(
                isset($this->resource['waiting_time_config']),
                fn () => $this->resource['waiting_time_config']
            ),
        ];
    }
}
