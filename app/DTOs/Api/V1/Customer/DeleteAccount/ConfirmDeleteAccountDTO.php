<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\DeleteAccount;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Customer;
use Illuminate\Http\Request;

class ConfirmDeleteAccountDTO implements RequestDataTransferObject
{
    public Customer $customer;

    public string $token;

    public function getDataFromRequest(Request $request): void
    {
        $this->customer = getAuthenticatedUser();
        $this->token = (string) $request->post('token');
    }
}
