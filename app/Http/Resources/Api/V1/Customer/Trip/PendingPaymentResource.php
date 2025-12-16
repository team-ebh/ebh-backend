<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

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
             * @example false
             */
            'has_pending_payment' => $this->resource,
        ];
    }
}
