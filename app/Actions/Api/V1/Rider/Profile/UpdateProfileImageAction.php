<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Profile;

use App\Interfaces\Repositories\Api\V1\Rider\RiderRepositoryInterface;
use App\Models\Rider;
use Illuminate\Http\UploadedFile;

readonly class UpdateProfileImageAction
{
    public function __construct(
        private RiderRepositoryInterface $riderRepository,
    ) {}

    public function __invoke(Rider $rider, UploadedFile $image): Rider
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->riderRepository->updateProfileImage($rider, $image));
    }
}
