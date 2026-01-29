<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\DeleteAccount;

use App\DTOs\Api\V1\Customer\DeleteAccount\VerifyDeleteAccountOtpDTO;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Services\DeleteAccountService;

readonly class VerifyDeleteAccountOtpAction
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
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
                $dto->customer,
                $dto->otp,
                fn ($customer, $otp) => $this->customerRepository->validateOtp($customer, $otp),
                fn ($customer) => $this->customerRepository->clearOtp($customer)
            ));
    }
}
