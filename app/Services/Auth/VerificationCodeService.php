<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\ApplicationEnvironmentEnum;
use App\Interfaces\Repositories\Api\V1\Customer\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Random\RandomException;

class VerificationCodeService
{
    public function isAlreadyHasActiveVerificationCode(Customer $customer): bool
    {
        return $customer->isOtpValid();
    }

    /**
     * @throws RandomException
     */
    public function generateCode(CustomerRepositoryInterface $customerRepo): string
    {
        return generateOtpCode();
    }

    /**
     * @throws RandomException
     */
    public function assignCodeToCustomer(Customer $customer, CustomerRepositoryInterface $customerRepo): Customer
    {
        $code = $this->generateCode($customerRepo);

        return $customerRepo->assignEmailVerificationCode($customer, $code);
    }

    public function sendVerificationCode(Customer $customer, VerificationCodeTypeEnum $type): void
    {
        Mail::to($customer->{Customer::COLUMN_EMAIL})
            ->send(new SendVerificationCodeMail($customer, $type));
    }

    public function isVerificationCodeBelongsToThisCustomer(Customer $customer, string $code): bool
    {
        if (ApplicationEnvironmentEnum::isDevelopmentEnvironment() && $code === config('auth.otp.code')) {
            return true;
        }

        return $code === $customer->{Customer::COLUMN_EMAIL_VERIFICATION_CODE};
    }

    public function isVerificationCodeActive(Customer $customer): bool
    {
        if (! $customer->{Customer::COLUMN_EMAIL_VERIFICATION_CODE_EXPIRES_AT}) {
            return false;
        }

        return $customer->{Customer::COLUMN_EMAIL_VERIFICATION_CODE_EXPIRES_AT} >= now()->timestamp;
    }
}
