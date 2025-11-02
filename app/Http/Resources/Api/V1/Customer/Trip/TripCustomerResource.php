<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Customer Resource
 *
 * Formats customer data for trip API responses
 */
class TripCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Customer $customer
         */
        $customer = $this->resource;

        return [
            /**
             * Customer ID
             *
             * @example 5
             *
             * @var int
             */
            'id' => $customer->id,

            /**
             * Customer full name
             *
             * @example "John Doe"
             *
             * @var string
             */
            'full_name' => $customer->full_name,
        ];
    }
}
