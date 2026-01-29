<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\DeleteAccount;

use App\DTOs\Api\V1\Rider\DeleteAccount\ConfirmDeleteAccountDTO;
use App\Services\DeleteAccountService;

readonly class ConfirmDeleteAccountAction
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
                $dto->rider,
                $dto->token
            ));
    }
}
