<?php

declare(strict_types=1);

namespace App\Repositories\Api\V1\Customer;

use App\DTOs\Api\V1\Customer\Auth\SignUpDTO;
use App\DTOs\Api\V1\Customer\Profile\UpdateProfileDTO;
use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Customer\CustomerAccountDisabledException;
use App\Exceptions\Customer\CustomerBeforeRegisteredException;
use App\Exceptions\Customer\CustomerMustBeRegisterException;
use App\Exceptions\Customer\CustomerNotFoundException;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;
use App\Models\Trip;
use App\Services\OtpVerificationService;
use Illuminate\Http\UploadedFile;
use Random\RandomException;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        protected OtpVerificationService $otpVerificationService,
    ) {}

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
        return $this->otpVerificationService->verify(
            $customer,
            $otp,
            Customer::COLUMN_OTP,
            Customer::COLUMN_PHONE_NUMBER
        );
    }

    public function clearOtp(Customer $customer): Customer
    {
        return $this->otpVerificationService->clear(
            $customer,
            Customer::COLUMN_OTP,
            Customer::COLUMN_OTP_EXPIRES_AT
        );
    }

    /**
     * @throws RandomException
     */
    public function generateOtp(Customer $customer): Customer
    {
        return $this->otpVerificationService->generate(
            $customer,
            Customer::COLUMN_OTP,
            Customer::COLUMN_OTP_EXPIRES_AT
        );
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
        throw_if($customer->isDisabled(), CustomerAccountDisabledException::class);
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
        $this->otpVerificationService->validate(
            $customer,
            $otp,
            Customer::COLUMN_OTP,
            Customer::COLUMN_PHONE_NUMBER
        );
    }

    public function createAuthToken(Customer $customer): string
    {
        return $customer->createToken('auth-token')->plainTextToken;
    }

    public function updateProfile(Customer $customer, UpdateProfileDTO $dto): Customer
    {
        $customer->update([
            Customer::COLUMN_FIRST_NAME => $dto->firstName,
            Customer::COLUMN_LAST_NAME => $dto->lastName,
            Customer::COLUMN_EMAIL => $dto->email,
            Customer::COLUMN_PHONE_NUMBER => $dto->phoneNumber,
        ]);

        return $customer->fresh();
    }

    public function updateProfileImage(Customer $customer, UploadedFile $image): Customer
    {
        // Clear existing profile photos
        $customer->clearMediaCollection(Customer::MEDIA_COLLECTION_NAME);

        // Add new profile photo
        $customer
            ->addMedia($image)
            ->withCustomProperties(['name' => Customer::PROFILE_PHOTO])
            ->toMediaCollection(Customer::MEDIA_COLLECTION_NAME);

        return $customer->fresh();
    }

    public function getCompletedTripsCount(int $customerId): int
    {
        return Trip::query()
            ->where(Trip::COLUMN_CUSTOMER_ID, $customerId)
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->count();
    }
}
