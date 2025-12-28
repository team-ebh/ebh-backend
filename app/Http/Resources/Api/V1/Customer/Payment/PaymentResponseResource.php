<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Payment;

use App\Services\Payment\DTOs\PaymentResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payment Response Resource
 *
 * @property PaymentResponseDTO $resource
 */
class PaymentResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => [
                'description' => 'Payment URL for completing the transaction',
                'type' => 'string',
                'example' => 'https://sandboxapi.upayments.com/payment/12345',
            ] + ['value' => $this->resource->redirectUrl],
        ];
    }
}
