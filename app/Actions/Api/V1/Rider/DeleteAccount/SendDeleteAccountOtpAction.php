<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\DeleteAccount;

use App\DTOs\Api\V1\Rider\DeleteAccount\SendDeleteAccountOtpDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Services\DeleteAccountService;

readonly class SendDeleteAccountOtpAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
        private DeleteAccountService $deleteAccountService,
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SendDeleteAccountOtpDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->deleteAccountService->sendOtp(
                $dto->rider,
                fn ($rider) => $this->riderRepository->generateOtp($rider)
            ));
    }
}
