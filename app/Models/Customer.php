<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Customer\CustomerStatusEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasMediaTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;

class Customer extends User implements HasMedia
{
    use HasApiTokens;
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use HasMediaTrait;
    use LogsActivity;

    public const string COLUMN_FIRST_NAME = 'first_name';

    public const string COLUMN_LAST_NAME = 'last_name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_OTP = 'otp';

    public const string COLUMN_OTP_EXPIRES_AT = 'otp_expires_at';

    public const string COLUMN_STATUS = 'status';

    public const string PROFILE_PHOTO = 'profile_photo';

    public const string MEDIA_COLLECTION_NAME = 'customers';

    protected $casts = [
        self::COLUMN_OTP_EXPIRES_AT => 'timestamp',
        self::COLUMN_STATUS => CustomerStatusEnum::class,
    ];

    protected $hidden = [
        self::COLUMN_OTP,
        self::COLUMN_OTP_EXPIRES_AT,
    ];

    protected $attributes = [
        self::COLUMN_STATUS => CustomerStatusEnum::PENDING_VERIFICATION,
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => trim($this->{self::COLUMN_FIRST_NAME} . ' ' . $this->{self::COLUMN_LAST_NAME}),
        );
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    protected function phoneNumberWithPrefix(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => defaultPrefixPhoneNumber() . $this->{Customer::COLUMN_PHONE_NUMBER},
        );
    }

    public function getPhoneNumberWithPrefix(): string
    {
        return $this->phone_number_with_prefix;
    }

    public function isOtpValid(): bool
    {
        return $this->{self::COLUMN_OTP} &&
            $this->{self::COLUMN_OTP_EXPIRES_AT} &&
            $this->{self::COLUMN_OTP_EXPIRES_AT} > now()->timestamp;
    }

    public function isActive(): bool
    {
        return $this->{self::COLUMN_STATUS} === CustomerStatusEnum::ACTIVE;
    }

    public function isPendingVerification(): bool
    {
        return $this->{self::COLUMN_STATUS} === CustomerStatusEnum::PENDING_VERIFICATION;
    }

    public function isDisabled(): bool
    {
        return in_array($this->{self::COLUMN_STATUS}, [
            CustomerStatusEnum::INACTIVE,
            CustomerStatusEnum::SUSPENDED,
        ]);
    }
}
