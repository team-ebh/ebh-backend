<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Profile;

use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;

readonly class UpdateProfileImageAction
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
    ) {}

    public function __invoke(Customer $customer, UploadedFile $image): Customer
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->customerRepository->updateProfileImage($customer, $image));
    }
}
