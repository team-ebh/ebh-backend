<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\DeleteAccount;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyDeleteAccountOtpDTO implements RequestDataTransferObject
{
    public Customer $customer;

    public string $otp;

    public function getDataFromRequest(Request $request): void
    {
        $this->customer = Auth::guard('customer')->user();
        $this->otp = (string) $request->post('otp');
    }
}
