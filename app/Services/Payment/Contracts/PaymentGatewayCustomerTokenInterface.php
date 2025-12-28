<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\CreateCustomerTokenDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

/**
 * Contract for all payment gateway implementations
 */
interface PaymentGatewayCustomerTokenInterface
{
    /**
     * Create customer unique token in payment gateway
     */
    public function createCustomerToken(CreateCustomerTokenDTO $dto): PaymentResponseDTO;
}
