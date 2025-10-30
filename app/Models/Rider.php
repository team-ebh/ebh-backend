<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\RiderStatusEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

class Rider extends User
{
    use HasApiTokens;
    use HasDefaultColumnModelTrait;
    use HasFactory;

    public const string COLUMN_FULL_NAME = 'full_name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_COMPANY_ID = 'company_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_OTP = 'otp';

    public const string COLUMN_OTP_EXPIRES_AT = 'otp_expires_at';

    protected $casts = [
        self::COLUMN_STATUS => RiderStatusEnum::class,
        self::COLUMN_OTP_EXPIRES_AT => 'timestamp',
    ];

    protected $hidden = [
        self::COLUMN_OTP,
        self::COLUMN_OTP_EXPIRES_AT,
    ];

    protected $attributes = [
        self::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, self::COLUMN_COMPANY_ID);
    }

    public function isOtpValid(): bool
    {
        return $this->{self::COLUMN_OTP} &&
            $this->{self::COLUMN_OTP_EXPIRES_AT} &&
            $this->{self::COLUMN_OTP_EXPIRES_AT} > now()->timestamp;
    }

    public function isOnline(): bool
    {
        return $this->{self::COLUMN_STATUS} === RiderStatusEnum::ONLINE;
    }

    public function isOffline(): bool
    {
        return $this->{self::COLUMN_STATUS} === RiderStatusEnum::OFFLINE;
    }

    public function isBusy(): bool
    {
        return $this->{self::COLUMN_STATUS} === RiderStatusEnum::BUSY;
    }
}
