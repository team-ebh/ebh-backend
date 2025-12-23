<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Customer\Payment;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPaymentStatusDTO implements RequestDataTransferObject
{
    public Payment $payment;

    public int $customerId;

    public function getDataFromRequest(Request $request): void
    {
        $this->payment = $request->route('payment');
        $this->customerId = Auth::guard('customer')->id();
    }
}
