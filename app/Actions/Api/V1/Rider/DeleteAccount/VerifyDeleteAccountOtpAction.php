<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\DeleteAccount;

use App\DTOs\Api\V1\Rider\DeleteAccount\VerifyDeleteAccountOtpDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Services\DeleteAccountService;

readonly class VerifyDeleteAccountOtpAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private DeleteAccountService $deleteAccountService,
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(VerifyDeleteAccountOtpDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->deleteAccountService->verifyOtpAndGenerateToken(
                $dto->rider,
                $dto->otp,
                fn ($rider, $otp) => $this->riderRepository->validateOtp($rider, $otp),
                fn ($rider) => $this->riderRepository->clearOtp($rider)
            ));
    }
}
