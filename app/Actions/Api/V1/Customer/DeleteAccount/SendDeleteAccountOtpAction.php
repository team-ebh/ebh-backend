<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\DeleteAccount;

use App\DTOs\Api\V1\Customer\DeleteAccount\SendDeleteAccountOtpDTO;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Services\DeleteAccountService;

readonly class SendDeleteAccountOtpAction
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
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
                $dto->customer,
                fn ($customer) => $this->customerRepository->generateOtp($customer)
            ));
    }
}
