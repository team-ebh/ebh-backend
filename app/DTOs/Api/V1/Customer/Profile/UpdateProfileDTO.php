<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Profile;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class UpdateProfileDTO implements RequestDataTransferObject
{
    public string $firstName;

    public string $lastName;

    public ?string $email;

    public string $phoneNumber;

    public function getDataFromRequest(Request $request): void
    {
        $this->firstName = $request->input('first_name');
        $this->lastName = $request->input('last_name');
        $this->email = $request->input('email');
        $this->phoneNumber = $request->input('phone_number');
    }
}
