<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Auth;

use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class SignUpVerifyOtpDTO implements RequestDataTransferObject
{
    public string $phoneNumber;

    public string $otp;

    public bool $mustBeSetActiveStatusCustomer;

    public function getDataFromRequest(Request $request): void
    {
        $this->phoneNumber = $request->post('phone_number');
        $this->otp = $request->post('otp');
        $this->mustBeSetActiveStatusCustomer = true;
    }
}
