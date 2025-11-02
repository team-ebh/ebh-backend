<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\Auth;

use App\Http\Resources\Api\V1\Rider\RiderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerifyOtpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Authentication token
             *
             * @example "1|abc123def456..."
             *
             * @var string
             */
            'token' => $this->resource['token'],

            /**
             * Rider information
             *
             * @var RiderResource
             */
            'rider' => new RiderResource($this->resource['rider']),
        ];
    }
}
