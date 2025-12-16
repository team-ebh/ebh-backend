<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Link Resource
 *
 * @property string $resource
 */
class PaymentLinkResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            'link' => $this->resource,
        ];
    }
}
