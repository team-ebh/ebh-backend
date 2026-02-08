<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer;

use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * @var Customer
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            /**
             * Customer ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource->{Customer::COLUMN_ID},

            /**
             * Customer first name
             *
             * @example "John"
             *
             * @var string
             */
            'first_name' => $this->resource->{Customer::COLUMN_FIRST_NAME},

            /**
             * Customer last name
             *
             * @example "Doe"
             *
             * @var string
             */
            'last_name' => $this->resource->{Customer::COLUMN_LAST_NAME},

            /**
             * Customer full name
             *
             * @example "John Doe"
             *
             * @var string
             */
            'full_name' => $this->resource->full_name,

            /**
             * Customer email address
             *
             * @example "john@example.com"
             *
             * @var string|null
             */
            'email' => $this->resource->{Customer::COLUMN_EMAIL},

            /**
             * Phone information
             *
             * @var PhoneCodeResource
             */
            'phone' => new PhoneCodeResource($this->resource->{Customer::COLUMN_PHONE_NUMBER}),

            /**
             * Customer profile image URL
             *
             * @example "https://example.com/storage/customers/1/profile.jpg"
             *
             * @var string|null
             */
            'image' => $this->resource->getFirstMediaLink(Customer::PROFILE_PHOTO) ?: getDefaultAvatar(),

            /**
             * Total number of completed trips
             *
             * @example 15
             *
             * @var int
             */
            'total_rides_count' => app(CustomerRepositoryInterface::class)
                ->getCompletedTripsCount($this->resource->{Customer::COLUMN_ID}),

            /**
             * Customer status information
             *
             * @var StatusResource
             */
            'status' => new StatusResource($this->resource->{Customer::COLUMN_STATUS}),
        ];
    }
}
