<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip\History;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Info Resource
 *
 * Formats customer information for trip history response
 */
class CustomerInfoHistoryTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            /**
             * Customer ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $customer->{Customer::COLUMN_ID},

            /**
             * Customer Name
             *
             * @example "Ahmed Al-Mansour"
             *
             * @var string
             */
            'name' => $customer->full_name,

            /**
             * Customer Image
             *
             * @example "https://api.ebhapp.com/customers/avatars/1.png"
             *
             * @var string|null
             */
            'image' => $customer->getFirstMediaLink(),
        ];
    }
}
