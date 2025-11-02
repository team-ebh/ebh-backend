<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Auth;

use App\Enums\Customer\CustomerStatusEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class SignUpDTO implements RequestDataTransferObject
{
    public string $firstName;

    public string $lastName;

    public ?string $email;

    public string $phoneNumber;

    public CustomerStatusEnum $status;

    public function getDataFromRequest(Request $request): void
    {
        $this->firstName = $request->post('first_name');
        $this->lastName = $request->post('last_name');
        $this->email = $request->post('email');
        $this->phoneNumber = $request->post('phone_number');
        $this->status = CustomerStatusEnum::PENDING_VERIFICATION;
    }
}
