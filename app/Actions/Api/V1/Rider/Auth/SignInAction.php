<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Auth;

use App\DTOs\Api\V1\Rider\Auth\SignInDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;

class SignInAction
{
    public function __construct(
        protected RiderRepositoryInterface $riderRepository,
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SignInDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->do([$this, 'signIn'], $dto);
    }

    public function signIn(SignInDTO $dto): array
    {
        $rider = $this->riderRepository->findAndValidateRider($dto->phoneNumber);

        if ($rider->isOtpValid()) {
            return $this->otpResponse($rider);
        }

        $rider = $this->riderRepository->generateOtp($rider);

        // TODO: Send OTP via SMS service
        // $this->sendOtpViaSms($rider->phone_number, $otp);

        return $this->otpResponse($rider);
    }

    private function otpResponse(Rider $rider): array
    {
        return [
            'otp_expires_at' => $rider->{Rider::COLUMN_OTP_EXPIRES_AT},
        ];
    }
}
