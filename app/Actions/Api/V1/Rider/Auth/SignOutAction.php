<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Auth;

use App\Models\Rider;
use Illuminate\Support\Facades\Auth;

class SignOutAction
{
    public function __invoke(): void
    {
        /** @var Rider $rider */
        $rider = Auth::guard('rider')->user();

        $rider?->currentAccessToken()->delete();
    }
}
