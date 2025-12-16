<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Payment;

use App\Enums\Payment\PaymentMethodEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Method Resource
 *
 * Formats payment method response
 *
 * @mixin PaymentMethodEnum
 */
class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Payment method id
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource->value,

            /**
             * Label
             *
             * @example "KNET"
             *
             * @var string
             */
            'label' => $this->resource->getLabel(),

            /**
             * Description
             *
             * @example "Online payment"
             *
             * @var string
             */
            'description' => $this->resource->getDescription(),

            /**
             * @example "http://api.ebhapp.com/images/payment_methods/cash.png"
             *
             * @var string
             */
            'icon' => $this->resource->getIcon(),

            /**
             * Is default
             *
             * @example false
             *
             * @var bool
             */
            'is_default' => false,
        ];
    }
}
