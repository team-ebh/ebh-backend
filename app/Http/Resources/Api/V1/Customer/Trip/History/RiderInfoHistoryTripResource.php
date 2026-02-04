<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip\History;

use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rider Info Resource
 *
 * Formats rider information for trip status response
 */
class RiderInfoHistoryTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Rider $rider */
        $rider = $this->resource;

        return [
            /**
             * Rider ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $rider->{Rider::COLUMN_ID},

            /**
             * Rider Image
             *
             * @example "https://api.ebhapp.com/riders/avatars/1.png"
             *
             * @var string
             */
            'image' => $rider->getFirstMediaLink() ?: getDefaultAvatar(),

            /**
             * Rider Name
             *
             * @example "Ahmed Al-Mansour"
             *
             * @var string
             */
            'name' => $rider->{Rider::COLUMN_FULL_NAME},

            /**
             * Rider Rating
             *
             * Average rating from 0 to 5
             *
             * @example 4.8
             *
             * @var float
             */
            'rating' => 4.8,
        ];
    }
}
