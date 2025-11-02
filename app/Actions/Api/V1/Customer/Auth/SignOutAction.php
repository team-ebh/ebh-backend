<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class SignOutAction
{
    public function __invoke(): void
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $customer?->currentAccessToken()->delete();
    }
}
