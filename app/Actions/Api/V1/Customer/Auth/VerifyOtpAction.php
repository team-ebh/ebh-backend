<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Auth;

use App\DTOs\Api\V1\Customer\Auth\SignInVerifyOtpDTO;
use App\DTOs\Api\V1\Customer\Auth\SignUpVerifyOtpDTO;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;

class VerifyOtpAction
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SignInVerifyOtpDTO | SignUpVerifyOtpDTO $dto): array
    {
        return safeProcess()->withTransaction()->do(function () use ($dto) {
            $customer = $this->customerRepository->findAndValidateCustomer($dto->phoneNumber);
            $this->customerRepository->validateOtp($customer, $dto->otp);

            if ($dto->mustBeSetActiveStatusCustomer) {
                $this->customerRepository->setActiveCustomer($customer);
            }

            $this->customerRepository->clearOtp($customer);
            $token = $this->customerRepository->createAuthToken($customer);

            return compact('token', 'customer');
        });
    }
}
