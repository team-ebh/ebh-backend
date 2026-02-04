<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SMS\SmsTypesEnum;
use App\Models\Customer;
use App\Models\Rider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpGenerated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Customer | Rider $user,
        public readonly string $otp,
        public readonly SmsTypesEnum $smsType,
    ) {}
}
