<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Profile;

use App\DTOs\Api\V1\Customer\Profile\UpdateProfileDTO;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;

readonly class UpdateProfileAction
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
    ) {}

    public function __invoke(Customer $customer, UpdateProfileDTO $dto): Customer
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(fn () => $this->customerRepository->updateProfile($customer, $dto));
    }
}
