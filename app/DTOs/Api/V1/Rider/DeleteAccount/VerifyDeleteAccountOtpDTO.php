<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\DeleteAccount;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Rider;
use Illuminate\Http\Request;

class VerifyDeleteAccountOtpDTO implements RequestDataTransferObject
{
    public Rider $rider;

    public string $otp;

    public function getDataFromRequest(Request $request): void
    {
        $this->rider = getAuthenticatedUser();
        $this->otp = (string) $request->post('otp');
    }
}
