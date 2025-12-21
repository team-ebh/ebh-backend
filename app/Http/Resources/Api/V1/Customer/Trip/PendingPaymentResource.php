<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Http\Resources\Api\PriceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Has pending payment
             *
             * Whether the customer has unpaid trips
             *
             * @var bool
             *
             * @example true
             */
            'has_pending_payment' => $this->resource['has_pending_payment'],

            /**
             * Price information
             *
             * Contains total price and currency if customer has pending payment, null otherwise
             *
             * @var PriceResource|null
             */
            'price' => ! is_null($this->resource['price'])
                ? new PriceResource($this->resource['price']['price'], $this->resource['price']['currency'])
                : null,
        ];
    }
}
