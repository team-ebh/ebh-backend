<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpGenerated;
use App\Services\SMS\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOtpSms implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected SmsService $smsService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OtpGenerated $event): void
    {
        $this->smsService->sendOtp(
            $event->user,
            $event->otp,
            $event->smsType
        );
    }
}
