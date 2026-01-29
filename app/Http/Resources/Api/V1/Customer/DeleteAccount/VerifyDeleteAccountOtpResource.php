<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\DeleteAccount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Verify Delete Account OTP Resource
 *
 * Returns security token for account deletion confirmation
 */
class VerifyDeleteAccountOtpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Security token for confirming account deletion
             *
             * @example "abc123xyz456..."
             *
             * @var string
             */
            'token' => $this->resource['token'],
        ];
    }
}
