<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use App\Http\Resources\Api\V1\Customer\PhoneCodeResource;
use App\Http\Resources\Api\V1\Customer\StatusResource;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderResource extends JsonResource
{
    /**
     * @var Rider
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            /**
             * Rider ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource->{Rider::COLUMN_ID},

            /**
             * Rider full name
             *
             * @example "John Doe"
             *
             * @var string
             */
            'full_name' => $this->resource->full_name,

            /**
             * Rider email address
             *
             * @example "john@example.com"
             *
             * @var string|null
             */
            'email' => $this->resource->{Rider::COLUMN_EMAIL},

            /**
             * Phone information
             *
             * @var PhoneCodeResource
             */
            'phone' => new PhoneCodeResource($this->resource->{Rider::COLUMN_PHONE_NUMBER}),

            /**
             * Rider profile image URL
             *
             * @example "https://example.com/storage/riders/1/profile.jpg"
             *
             * @var string|null
             */
            'image' => $this->resource->getFirstMediaLink(Rider::PROFILE_PHOTO) ?: getDefaultAvatar(),

            /**
             * Total number of completed trips
             *
             * @example 25
             *
             * @var int
             */
            'total_rides_count' => app(RiderRepositoryInterface::class)
                ->getCompletedTripsCount($this->resource->{Rider::COLUMN_ID}),

            /**
             * Rider Rating
             *
             * Average rating from 0 to 5
             *
             * @example 4.8
             *
             * @var float
             */
            'rating' => 4.6,

            /**
             * Rider status information
             *
             * @var StatusResource
             */
            'status' => new StatusResource($this->resource->{Rider::COLUMN_STATUS}),

            /**
             * Rider's vehicle information
             *
             * @var VehicleResource|null
             */
            'vehicle' => $this->resource->vehicle
                ? new VehicleResource($this->resource->vehicle)
                : null,

            /**
             * Account joined timestamp (Unix timestamp)
             *
             * @example 1705315800
             *
             * @var int|null
             */
            'joined_at' => $this->resource->{Rider::COLUMN_CREATED_AT}?->timestamp,
        ];
    }
}
