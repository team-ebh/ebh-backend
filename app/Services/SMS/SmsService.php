<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Enums\ApplicationEnvironmentEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Services\SMS\DTOs\DeleteAccountSmsDTO;
use App\Services\SMS\DTOs\SignInSmsDTO;
use App\Services\SMS\DTOs\SignUpSmsDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send OTP SMS to user (Customer or Rider)
     *
     * @param  \App\Models\Customer|\App\Models\Rider  $receiver
     */
    public function sendOtp(Model $receiver, string $otp, SmsTypesEnum $smsType): bool
    {
        try {
            $message = $this->buildOtpMessage($otp, $smsType);

            $dtoClass = match ($smsType) {
                SmsTypesEnum::SIGNUP => SignUpSmsDTO::class,
                SmsTypesEnum::SIGNIN => SignInSmsDTO::class,
                SmsTypesEnum::DELETE_ACCOUNT => DeleteAccountSmsDTO::class,
            };

            $dto = new $dtoClass;
            $dto->getDataFromArray([
                'receiver' => $receiver,
                'message' => $message,
            ]);

            // Skip SMS sending if conditions are met
            if (! $this->shouldSendSms($dto->phoneNumber)) {
                Log::info('SMS skipped (test mode or test number)', [
                    'phone_number' => $dto->phoneNumber,
                    'receiver_type' => get_class($receiver),
                    'receiver_id' => $receiver->id,
                    'sms_type' => $smsType->name,
                    'is_test_number' => $this->isTestPhoneNumber($dto->phoneNumber),
                ]);

                return true; // Return true to indicate OTP was "sent" successfully
            }

            $smsProvider = SmsFactory::build();
            $result = $smsProvider->send($dto);

            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            Log::error('SMS sending failed', [
                'error' => $e->getMessage(),
                'receiver_type' => get_class($receiver),
                'receiver_id' => $receiver->id,
                'sms_type' => $smsType->name,
            ]);

            return false;
        }
    }

    /**
     * Check if SMS should be sent based on environment and phone number
     */
    private function shouldSendSms(string $phoneNumber): bool
    {
        // Skip SMS for test phone numbers in all environments
        if ($this->isTestPhoneNumber($phoneNumber)) {
            return false;
        }

        // Only send SMS in production or staging environments
        return ApplicationEnvironmentEnum::isProduction()
            || ApplicationEnvironmentEnum::isStage();
    }

    /**
     * Check if phone number is a test number
     */
    private function isTestPhoneNumber(string $phoneNumber): bool
    {
        $testNumbers = config('sms.test_mode.test_phone_numbers', []);

        return in_array($phoneNumber, $testNumbers, true);
    }

    /**
     * Build OTP message based on SMS type using translations
     */
    private function buildOtpMessage(string $otp, SmsTypesEnum $smsType): string
    {
        $translationKey = match ($smsType) {
            SmsTypesEnum::SIGNUP => 'sms.signup',
            SmsTypesEnum::SIGNIN => 'sms.signin',
            SmsTypesEnum::DELETE_ACCOUNT => 'sms.delete_account',
        };

        return __($translationKey, ['otp' => $otp]);
    }
}
