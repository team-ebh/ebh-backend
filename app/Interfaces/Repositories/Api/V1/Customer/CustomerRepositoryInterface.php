<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\Auth\SignUpDTO;
use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?Customer;

    public function findAndValidateCustomer(string $phoneNumber): ?Customer;

    public function updateOrCreateCustomer(SignUpDTO $dto): Customer;

    public function generateOtp(Customer $customer): Customer;

    public function setActiveCustomer(Customer $customer): Customer;

    public function verifyOtp(Customer $customer, string $otp): bool;

    public function clearOtp(Customer $customer): Customer;

    public function checkCustomerCanSignUp(Customer $customer): void;

    public function checkCustomerCanSignIn(Customer $customer): void;

    public function validateOtp(Customer $customer, string $otp): void;

    public function createAuthToken(Customer $customer): string;
}
