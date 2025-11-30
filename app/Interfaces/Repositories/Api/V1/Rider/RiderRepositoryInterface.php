<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Rider;

use App\Models\Rider;

interface RiderRepositoryInterface
{
    public function find(int $riderId): ?Rider;

    public function findByPhoneNumber(string $phoneNumber): ?Rider;

    public function findAndValidateRider(string $phoneNumber): ?Rider;

    public function generateOtp(Rider $rider): Rider;

    public function verifyOtp(Rider $rider, string $otp): bool;

    public function clearOtp(Rider $rider): Rider;

    public function validateOtp(Rider $rider, string $otp): void;

    public function createAuthToken(Rider $rider): string;

    public function updateLocation(Rider $rider, float $latitude, float $longitude): void;
}
