<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Interfaces\DTOs\ArrayDataTransferObject;

/**
 * Create Customer Token DTO
 *
 * Data transfer object for creating customer unique token in payment gateway
 */
class CreateCustomerTokenDTO implements ArrayDataTransferObject
{
    public int $customerId;

    public string $customerUniqueToken;

    public function getDataFromArray(array $data): void
    {
        $this->customerId = $data['customer_id'];
        $this->customerUniqueToken = $data['customer_unique_token'];
    }
}
