<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\DeleteAccount;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SendDeleteAccountOtpDTO implements RequestDataTransferObject
{
    public Rider $rider;

    public function getDataFromRequest(Request $request): void
    {
        $this->rider = Auth::guard('rider')->user();
    }
}
