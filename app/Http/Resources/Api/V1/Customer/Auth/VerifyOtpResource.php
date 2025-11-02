<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Auth;

use App\Http\Resources\Api\V1\Customer\CustomerResource;
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
             * Customer information
             *
             * @var CustomerResource
             */
            'customer' => new CustomerResource($this->resource['customer']),
        ];
    }
}
