<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider;

use App\Http\Resources\Api\V1\Customer\PhoneCodeResource;
use App\Http\Resources\Api\V1\Customer\StatusResource;
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
             * Rider status information
             *
             * @var StatusResource
             */
            'status' => new StatusResource($this->resource->{Rider::COLUMN_STATUS}),
        ];
    }
}
