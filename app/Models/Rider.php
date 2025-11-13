<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\RiderStatusEnum;
use App\Traits\Model\Aggregates\RiderAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasMediaTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;

class Rider extends User implements HasMedia
{
    use HasApiTokens;
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use HasMediaTrait;
    use LogsActivity;
    use RiderAggregate;

    protected $touches = ['documents', 'media'];

    public const string COLUMN_FULL_NAME = 'full_name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_COMPANY_ID = 'company_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_OTP = 'otp';

    public const string COLUMN_OTP_EXPIRES_AT = 'otp_expires_at';

    public const string COLUMN_ACCESSIBILITY_CERTIFICATIONS = 'accessibility_certifications';

    public const string PROFILE_PHOTO = 'profile_photo';

    public const string MEDIA_COLLECTION_NAME = 'riders';

    protected $casts = [
        self::COLUMN_STATUS => RiderStatusEnum::class,
        self::COLUMN_OTP_EXPIRES_AT => 'timestamp',
        self::COLUMN_ACCESSIBILITY_CERTIFICATIONS => 'json',
    ];

    protected $hidden = [
        self::COLUMN_OTP,
        self::COLUMN_OTP_EXPIRES_AT,
    ];

    protected $attributes = [
        self::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
    ];

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

    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class, Vehicle::COLUMN_RIDER_ID);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, Vehicle::COLUMN_RIDER_ID);
    }
}
