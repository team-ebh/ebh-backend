<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Payment;

use App\Http\Resources\Api\PriceResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Check Payment Status Resource
 *
 * @property Payment $resource
 */
class CheckPaymentStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Trip ID
             *
             * @example 123
             *
             * @var int|null
             */
            'trip_id' => $this->resource->order?->trips?->first()?->{Trip::COLUMN_ID},

            /**
             * Order ID
             *
             * @example "TRP-123"
             *
             * @var string|null
             */
            'order_id' => tripNumberFormat($this->resource->order?->trips?->first()),

            /**
             * Payment created date
             *
             * @var int
             *
             * @example 1763453833
             */
            'date_time' => $this->resource->{Payment::COLUMN_UPDATED_AT}?->timestamp,

            /**
             * Payment method
             *
             * @example "KNET"
             *
             * @var string
             */
            'payment_method' => $this->resource->order?->{Order::COLUMN_PAYMENT_METHOD}?->getLabel(),

            /**
             * Payment number
             *
             * @example "PAY-123456789"
             *
             * @var string
             */
            'payment_number' => $this->resource->{Payment::COLUMN_PAYMENT_NUMBER},

            /**
             * Payment status
             *
             * @example "Success"
             *
             * @var string
             */
            'payment_status' => $this->resource->{Payment::COLUMN_STATUS}?->getFrontendLabel(),

            /**
             * Payment price details
             *
             * @var array
             */
            'payment' => new PriceResource(
                (float) $this->resource->{Payment::COLUMN_AMOUNT},
                $this->resource->{Payment::COLUMN_CURRENCY}
            ),
        ];
    }
}
