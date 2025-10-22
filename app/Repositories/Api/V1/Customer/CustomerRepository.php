<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\Auth\SignUpDTO;
use App\Enums\Customer\CustomerStatusEnum;
use App\Exceptions\Customer\CustomerBeforeRegisteredException;
use App\Exceptions\Customer\CustomerMustBeRegisterException;
use App\Exceptions\Customer\CustomerNotFoundException;
use App\Exceptions\Customer\InvalidOtpException;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;
use Random\RandomException;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function findByPhoneNumber(string $phoneNumber): ?Customer
    {
        return Customer::query()->where(Customer::COLUMN_PHONE_NUMBER, $phoneNumber)->first();
    }

    /**
     * @throws \Throwable
     */
    public function findAndValidateCustomer(string $phoneNumber): ?Customer
    {
        $customer = $this->findByPhoneNumber($phoneNumber);

        throw_if(! $customer, CustomerNotFoundException::class);

        return $customer;
    }

    public function updateOrCreateCustomer(SignUpDTO $dto): Customer
    {
        return Customer::query()
            ->updateOrCreate(
                [
                    Customer::COLUMN_PHONE_NUMBER => $dto->phoneNumber,
                ],
                [
                    Customer::COLUMN_FIRST_NAME => $dto->firstName,
                    Customer::COLUMN_LAST_NAME => $dto->lastName,
                    Customer::COLUMN_EMAIL => $dto->email,
                ]
            );
    }

    public function verifyOtp(Customer $customer, string $otp): bool
    {
        // TODO::temp valid test otp code
        //        return $customer->isOtpValid() && $otp === $customer->{Customer::COLUMN_OTP};
        return $customer->isOtpValid() && ($otp === config('sms.test_mode.otp_code') || $otp === $customer->{Customer::COLUMN_OTP});
    }

    public function clearOtp(Customer $customer): Customer
    {
        $customer->update([
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return $customer->fresh();
    }

    /**
     * @throws RandomException
     */
    public function generateOtp(Customer $customer): Customer
    {
        $customer
            ->update([
                Customer::COLUMN_OTP => generateOtpCode(),
                Customer::COLUMN_OTP_EXPIRES_AT => now()->addSeconds(config('sms.otp_timeout')),
            ]);

        return $customer->fresh();
    }

    /**
     * @throws \Throwable
     */
    public function checkCustomerCanSignUp(Customer $customer): void
    {
        throw_if($customer->isActive(), CustomerBeforeRegisteredException::class);
    }

    /**
     * @throws \Throwable
     */
    public function checkCustomerCanSignIn(Customer $customer): void
    {
        throw_if($customer->isPendingVerification(), CustomerMustBeRegisterException::class);
    }

    public function setActiveCustomer(Customer $customer): Customer
    {
        $customer->update([Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE]);

        return $customer->fresh();
    }

    /**
     * @throws \Throwable
     */
    public function validateOtp(Customer $customer, string $otp): void
    {
        throw_if(! $this->verifyOtp($customer, $otp), InvalidOtpException::class);
    }

    public function createAuthToken(Customer $customer): string
    {
        return $customer->createToken('auth-token')->plainTextToken;
    }
}
