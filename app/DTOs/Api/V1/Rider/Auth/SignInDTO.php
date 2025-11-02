<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Auth;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class SignInDTO implements RequestDataTransferObject
{
    public string $phoneNumber;

    public function getDataFromRequest(Request $request): void
    {
        $this->phoneNumber = $request->post('phone_number');
    }
}
