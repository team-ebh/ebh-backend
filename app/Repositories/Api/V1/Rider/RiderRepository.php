<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Rider;

use App\DTOs\Api\V1\Rider\Profile\UpdateProfileDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Customer\InvalidOtpException;
use App\Exceptions\Customer\RiderNotFoundException;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use App\Models\Trip;
use Illuminate\Http\UploadedFile;
use Random\RandomException;

class RiderRepository implements RiderRepositoryInterface
{
    public function find(int $riderId): ?Rider
    {
        return Rider::find($riderId);
    }

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

    public function updateLocation(Rider $rider, float $latitude, float $longitude): void
    {
        $rider->update([
            Rider::COLUMN_LATITUDE => $latitude,
            Rider::COLUMN_LONGITUDE => $longitude,
            Rider::COLUMN_LAST_LOCATION_UPDATE => now(),
        ]);
    }

    public function updateProfile(Rider $rider, UpdateProfileDTO $dto): Rider
    {
        $data = [
            Rider::COLUMN_FULL_NAME => $dto->fullName,
            Rider::COLUMN_PHONE_NUMBER => $dto->phoneNumber,
        ];

        // Only update email if provided (nullable in request, but NOT NULL in database)
        if ($dto->email !== null) {
            $data[Rider::COLUMN_EMAIL] = $dto->email;
        }

        $rider->update($data);

        return $rider->fresh();
    }

    public function updateProfileImage(Rider $rider, UploadedFile $image): Rider
    {
        // Clear existing profile photos
        $rider->clearMediaCollection(Rider::MEDIA_COLLECTION_NAME);

        // Add new profile photo
        $rider
            ->addMedia($image)
            ->withCustomProperties(['name' => Rider::PROFILE_PHOTO])
            ->toMediaCollection(Rider::MEDIA_COLLECTION_NAME);

        return $rider->fresh();
    }

    public function getCompletedTripsCount(int $riderId): int
    {
        return Trip::query()
            ->where(Trip::COLUMN_RIDER_ID, $riderId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->count();
    }
}
