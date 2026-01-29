<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Profile;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class UpdateProfileDTO implements RequestDataTransferObject
{
    public string $fullName;

    public ?string $email;

    public string $phoneNumber;

    public function getDataFromRequest(Request $request): void
    {
        $this->fullName = $request->input('full_name');
        $this->email = $request->input('email');
        $this->phoneNumber = $request->input('phone_number');
    }
}
