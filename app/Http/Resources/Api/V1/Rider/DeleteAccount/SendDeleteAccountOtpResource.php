<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Rider\DeleteAccount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Send Delete Account OTP Resource
 *
 * Returns OTP expiration timestamp
 */
class SendDeleteAccountOtpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * OTP expiration timestamp
             *
             * @example 1705932800
             *
             * @var int
             */
            'otp_expires_at' => $this->resource['otp_expires_at'],
        ];
    }
}
