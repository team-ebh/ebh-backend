<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Receipt Link Resource
 */
class PaymentReceiptLinkResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Temporary signed URL to download receipt (valid for 30 minutes)
             *
             * @example "https://api.ebh.com/v1/payments/PAY-123456789/download-receipt?expires=1234567890&signature=abc123..."
             *
             * @var string
             */
            'link' => $this->resource['link'],
        ];
    }
}
