<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Rider\RiderStatusEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Events\OtpGenerated;
use App\Exceptions\Customer\InvalidOtpException;
use App\Exceptions\InvalidSecurityTokenException;
use App\Models\Customer;
use App\Models\DeleteAccountRequest;
use App\Models\Rider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared service for delete account functionality
 *
 * Handles delete account flow for both Customer and Rider:
 * 1. Send OTP (reuses existing OTP infrastructure)
 * 2. Verify OTP and generate security token
 * 3. Confirm deletion with security token
 */
class DeleteAccountService
{
    private const int SECURITY_TOKEN_LENGTH = 32;

    private const int SECURITY_TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Send OTP for delete account verification
     * Reuses existing OTP infrastructure from auth
     *
     * @param  Customer|Rider  $user
     */
    public function sendOtp(Model $user, callable $generateOtpCallback): array
    {
        // Check if user already has a valid OTP
        if ($user->isOtpValid()) {
            return [
                'otp_expires_at' => $user->otp_expires_at,
            ];
        }

        // Generate new OTP using the repository callback
        $user = $generateOtpCallback($user);

        // Determine OTP column based on user type
        $otpColumn = match ($user::class) {
            Customer::class => Customer::COLUMN_OTP,
            Rider::class => Rider::COLUMN_OTP,
            default => throw new \InvalidArgumentException('Unsupported user type'),
        };

        event(new OtpGenerated(
            $user,
            $user->{$otpColumn},
            SmsTypesEnum::DELETE_ACCOUNT
        ));

        return [
            'otp_expires_at' => $user->otp_expires_at,
        ];
    }

    /**
     * Verify OTP and generate security token
     *
     * @param  Customer|Rider  $user
     *
     * @throws InvalidOtpException
     */
    public function verifyOtpAndGenerateToken(
        Model $user,
        string $otp,
        callable $validateOtpCallback,
        callable $clearOtpCallback
    ): array {
        // Validate OTP using the repository callback
        $validateOtpCallback($user, $otp);

        // Clear OTP after validation
        $clearOtpCallback($user);

        // Generate security token
        $securityToken = $this->generateSecurityToken();

        // Create or update delete account request
        $this->createOrUpdateDeleteRequest($user, $securityToken);

        return [
            'token' => $securityToken,
        ];
    }

    /**
     * Confirm account deletion
     *
     * @param  Customer|Rider  $user
     *
     * @throws InvalidSecurityTokenException
     */
    public function confirmDeletion(Model $user, string $token): void
    {
        // Find and validate delete request
        $deleteRequest = $this->findValidDeleteRequest($user, $token);

        if (! $deleteRequest) {
            throw new InvalidSecurityTokenException;
        }

        // Mark account as deleted
        $this->markAccountAsDeleted($user, $deleteRequest);

        // Revoke all tokens
        $user->tokens()->delete();
    }

    /**
     * Generate a secure random token
     */
    private function generateSecurityToken(): string
    {
        return Str::random(self::SECURITY_TOKEN_LENGTH);
    }

    /**
     * Create or update delete account request with security token
     *
     * @param  Customer|Rider  $user
     */
    private function createOrUpdateDeleteRequest(Model $user, string $securityToken): DeleteAccountRequest
    {
        return DeleteAccountRequest::query()->updateOrCreate(
            [
                DeleteAccountRequest::COLUMN_REQUESTABLE_TYPE => $user::class,
                DeleteAccountRequest::COLUMN_REQUESTABLE_ID => $user->id,
                DeleteAccountRequest::COLUMN_ACCOUNT_DELETED_AT => null,
            ],
            [
                DeleteAccountRequest::COLUMN_SECURITY_TOKEN => $securityToken,
                DeleteAccountRequest::COLUMN_SECURITY_TOKEN_EXPIRES_AT => now()->addMinutes(self::SECURITY_TOKEN_EXPIRY_MINUTES),
            ]
        );
    }

    /**
     * Find valid delete request by user and token
     *
     * @param  Customer|Rider  $user
     */
    private function findValidDeleteRequest(Model $user, string $token): ?DeleteAccountRequest
    {
        $deleteRequest = DeleteAccountRequest::query()
            ->where(DeleteAccountRequest::COLUMN_REQUESTABLE_TYPE, $user::class)
            ->where(DeleteAccountRequest::COLUMN_REQUESTABLE_ID, $user->id)
            ->where(DeleteAccountRequest::COLUMN_SECURITY_TOKEN, $token)
            ->whereNull(DeleteAccountRequest::COLUMN_ACCOUNT_DELETED_AT)
            ->first();

        if (! $deleteRequest || ! $deleteRequest->isSecurityTokenValid()) {
            return null;
        }

        return $deleteRequest;
    }

    /**
     * Mark account as deleted and anonymize phone number
     *
     * @param  Customer|Rider  $user
     */
    private function markAccountAsDeleted(Model $user, DeleteAccountRequest $deleteRequest): void
    {
        // Log deletion timestamp
        $deleteRequest->update([
            DeleteAccountRequest::COLUMN_ACCOUNT_DELETED_AT => now(),
            DeleteAccountRequest::COLUMN_SECURITY_TOKEN => null,
            DeleteAccountRequest::COLUMN_SECURITY_TOKEN_EXPIRES_AT => null,
        ]);

        // Anonymize phone number and set status to deleted
        $originalPhoneNumber = $user->phone_number;
        $newPhoneNumber = $originalPhoneNumber . '-deleted';
        $counter = 1;

        // Handle potential duplicates
        while ($user::query()->where('phone_number', $newPhoneNumber)->exists()) {
            $newPhoneNumber = $originalPhoneNumber . "-deleted-{$counter}";
            $counter++;
        }

        // Update user status based on model type
        $status = match ($user::class) {
            Customer::class => CustomerStatusEnum::DELETED,
            Rider::class => RiderStatusEnum::DELETED,
            default => throw new \InvalidArgumentException('Unsupported user type'),
        };

        $user->update([
            'phone_number' => $newPhoneNumber,
            'status' => $status,
        ]);
    }
}
