<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Auth;

use App\DTOs\Api\V1\Rider\Auth\SignInVerifyOtpDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;

class VerifyOtpAction
{
    public function __construct(
        protected RiderRepositoryInterface $riderRepository
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SignInVerifyOtpDTO $dto): array
    {
        return safeProcess()->withTransaction()->do(function () use ($dto) {
            $rider = $this->riderRepository->findAndValidateRider($dto->phoneNumber);
            $this->riderRepository->validateOtp($rider, $dto->otp);

            $this->riderRepository->clearOtp($rider);
            $token = $this->riderRepository->createAuthToken($rider);

            return compact('token', 'rider');
        });
    }
}
