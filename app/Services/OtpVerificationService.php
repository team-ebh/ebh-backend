<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationEnvironmentEnum;
use App\Exceptions\Customer\InvalidOtpException;
use App\Models\Customer;
use App\Models\Rider;

class OtpVerificationService
{
    /**
     * Generate OTP for a user
     */
    public function generate(Rider | Customer $user, string $otpColumn, string $otpExpiresAtColumn): Rider | Customer
    {
        $user->update([
            $otpColumn => generateOtpCode(),
            $otpExpiresAtColumn => now()->addSeconds(config('sms.otp_timeout')),
        ]);

        return $user->fresh();
    }

    /**
     * Verify OTP for a user
     */
    public function verify(Rider | Customer $user, string $otp, string $otpColumn, string $phoneNumberColumn): bool
    {
        // In local/test/dev environments, accept any OTP
        if (! ApplicationEnvironmentEnum::isRiskyEnvironment()) {
            return true;
        }

        // In production/stage environments:
        // Check if test mode is enabled and phone number is in test list
        $testPhoneNumbers = config('sms.test_mode.test_phone_numbers', []);
        $defaultTestOtp = config('sms.test_mode.default_otp', '0421');

        $isTestPhoneNumber = in_array($user->{$phoneNumberColumn}, $testPhoneNumbers);

        // If test mode and test phone number, allow default OTP
        if ($isTestPhoneNumber && $otp === $defaultTestOtp) {
            return true;
        }

        // Normal OTP validation
        return $user->isOtpValid() && $otp === $user->{$otpColumn};
    }

    /**
     * Validate OTP and throw exception if invalid
     *
     *
     * @throws InvalidOtpException
     * @throws \Throwable
     */
    public function validate(Rider | Customer $user, string $otp, string $otpColumn, string $phoneNumberColumn): void
    {
        throw_if(
            ! $this->verify($user, $otp, $otpColumn, $phoneNumberColumn),
            InvalidOtpException::class
        );
    }

    /**
     * Clear OTP for a user
     */
    public function clear(Rider | Customer $user, string $otpColumn, string $otpExpiresAtColumn): Rider | Customer
    {
        $user->update([
            $otpColumn => null,
            $otpExpiresAtColumn => null,
        ]);

        return $user->fresh();
    }
}
