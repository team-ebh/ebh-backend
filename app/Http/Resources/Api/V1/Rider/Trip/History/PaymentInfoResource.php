<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Info Resource
 *
 * Formats payment information for past trip details
 *
 * @mixin Order
 */
class PaymentInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        /** @var PaymentMethodEnum $paymentMethod */
        $paymentMethod = $order->{Order::COLUMN_PAYMENT_METHOD};

        /** @var OrderStatusEnum $status */
        $status = $order->{Order::COLUMN_STATUS};

        /** @var CurrencyEnum $currency */
        $currency = $order->{Order::COLUMN_CURRENCY};

        return [
            /**
             * Payment method information
             *
             * @var array{id: int, label: string}
             */
            'payment_method' => [
                /**
                 * Payment method id
                 *
                 * @example 1
                 *
                 * @var int
                 */
                'id' => $paymentMethod->value,

                /**
                 * Payment method label
                 *
                 * @example "KNET"
                 *
                 * @var string
                 */
                'label' => $paymentMethod->getLabel(),
            ],

            /**
             * Payment status
             *
             * @var StatusResource
             */
            'status' => new StatusResource($status),

            /**
             * Total amount
             *
             * @example "15.500"
             *
             * @var string
             */
            'total_amount' => $order->{Order::COLUMN_TOTAL_PRICE},

            /**
             * Currency code
             *
             * @example "KWD"
             *
             * @var string
             */
            'currency' => $currency->value,
        ];
    }
}
