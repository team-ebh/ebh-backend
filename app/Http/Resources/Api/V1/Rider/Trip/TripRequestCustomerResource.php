<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Trip;

use App\Http\Resources\Api\V1\Customer\PhoneCodeResource;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trip Resource
 *
 * Formats trip data for rider API responses
 *
 * @property Trip $resource
 */
class TripRequestCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Customer name
             *
             * @var string
             *
             * @example "Test Customer"
             */
            'full_name' => $this->resource->full_name,

            /**
             * Customer phone number
             *
             * @var PhoneCodeResource
             */
            'phone' => new PhoneCodeResource($this->resource->{Customer::COLUMN_PHONE_NUMBER}),

            /**
             * Customer Image
             *
             * @example "https://api.ebhapp.com/customers/avatars/1.png"
             *
             * @var string
             */
            'image' => $this->resource['image'],
        ];
    }
}
