<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use App\Http\Resources\Api\V1\Customer\PhoneCodeResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Customer Resource for Rider API
 *
 * Formats customer data for rider API responses
 *
 * @property Customer $resource
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Customer full name
             *
             * @var string
             *
             * @example "John Doe"
             */
            'full_name' => $this->resource->full_name,

            /**
             * Customer profile image URL
             *
             * @var string|null
             *
             * @example "https://example.com/images/customer.jpg"
             */
            'image' => null, // Customer model doesn't have media implementation yet

            /**
             * Customer phone information
             *
             * @var PhoneCodeResource
             */
            'phone' => new PhoneCodeResource($this->resource->{Customer::COLUMN_PHONE_NUMBER}),
        ];
    }
}
