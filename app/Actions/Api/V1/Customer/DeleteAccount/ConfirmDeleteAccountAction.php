<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\DeleteAccount;

use App\DTOs\Api\V1\Customer\DeleteAccount\ConfirmDeleteAccountDTO;
use App\Services\DeleteAccountService;

class ConfirmDeleteAccountAction
{
    public function __construct(
        private DeleteAccountService $deleteAccountService,
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(ConfirmDeleteAccountDTO $dto): void
    {
        safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->deleteAccountService->confirmDeletion(
                $dto->customer,
                $dto->token
            ));
    }
}
