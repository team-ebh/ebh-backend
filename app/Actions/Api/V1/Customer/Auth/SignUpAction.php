<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Auth;

use App\DTOs\Api\V1\Customer\Auth\SignUpDTO;
use App\Enums\SMS\SmsTypesEnum;
use App\Events\OtpGenerated;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;

class SignUpAction
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SignUpDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->do([$this, 'signUp'], $dto);
    }

    /**
     * @throws \Throwable
     */
    public function signUp(SignUpDTO $dto): array
    {
        $customer = $this->customerRepository->updateOrCreateCustomer($dto);
        $this->customerRepository->checkCustomerCanSignUp($customer);

        if ($customer->isOtpValid()) {
            return $this->otpResponse($customer);
        }

        $customer = $this->customerRepository->generateOtp($customer);

        event(new OtpGenerated(
            $customer,
            $customer->{Customer::COLUMN_OTP},
            SmsTypesEnum::SIGNUP
        ));

        return $this->otpResponse($customer);
    }

    private function otpResponse(Customer $customer): array
    {
        return [
            'otp_expires_at' => $customer->{Customer::COLUMN_OTP_EXPIRES_AT},
        ];
    }
}
