<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * OTP expiration timestamp
             *
             * @example 1640995200
             *
             * @var int
             */
            'otp_expires_at' => $this->resource['otp_expires_at'],
        ];
    }
}
