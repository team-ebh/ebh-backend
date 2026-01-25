<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Profile;

use App\DTOs\Api\V1\Rider\Profile\UpdateProfileDTO;
use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;

readonly class UpdateProfileAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
    ) {}

    public function __invoke(Rider $rider, UpdateProfileDTO $dto): Rider
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->riderRepository->updateProfile($rider, $dto));
    }
}
