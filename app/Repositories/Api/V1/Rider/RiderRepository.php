<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider;

use App\Exceptions\Customer\InvalidOtpException;
use App\Exceptions\Customer\RiderNotFoundException;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use Random\RandomException;

class RiderRepository implements RiderRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?Rider
    {
        return Rider::query()->where(Rider::COLUMN_PHONE_NUMBER, $phoneNumber)->first();
    }

    /**
     * @throws \Throwable
     */
    public function findAndValidateRider(string $phoneNumber): ?Rider
    {
        $rider = $this->findByPhoneNumber($phoneNumber);

        throw_if(! $rider, RiderNotFoundException::class);

        return $rider;
    }

    public function verifyOtp(Rider $rider, string $otp): bool
    {
        return true;

        // TODO::temp valid test otp code
        //        return $rider->isOtpValid() && $otp === $rider->{Rider::COLUMN_OTP};
        return $rider->isOtpValid() && ($otp === config('sms.test_mode.otp_code') || $otp === $rider->{Rider::COLUMN_OTP});
    }

    public function clearOtp(Rider $rider): Rider
    {
        $rider->update([
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return $rider->fresh();
    }

    /**
     * @throws RandomException
     */
    public function generateOtp(Rider $rider): Rider
    {
        $rider
            ->update([
                Rider::COLUMN_OTP => generateOtpCode(),
                Rider::COLUMN_OTP_EXPIRES_AT => now()->addSeconds(config('sms.otp_timeout')),
            ]);

        return $rider->fresh();
    }

    /**
     * @throws \Throwable
     */
    public function validateOtp(Rider $rider, string $otp): void
    {
        throw_if(! $this->verifyOtp($rider, $otp), InvalidOtpException::class);
    }

    public function createAuthToken(Rider $rider): string
    {
        return $rider->createToken('auth-token')->plainTextToken;
    }
}
