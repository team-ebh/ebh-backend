<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\RiderStatusEnum;
use App\Observers\RiderObserver;
use App\Traits\Model\Aggregates\RiderAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasEnabledTrait;
use App\Traits\Model\HasMediaTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;

#[ObservedBy(RiderObserver::class)]
class Rider extends User implements HasMedia
{
    use HasApiTokens;
    use HasDefaultColumnModelTrait;
    use HasEnabledTrait;
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

    public const string COLUMN_LATITUDE = 'latitude';

    public const string COLUMN_LONGITUDE = 'longitude';

    public const string COLUMN_LAST_LOCATION_UPDATE = 'last_location_update';

    public const string PROFILE_PHOTO = 'profile_photo';

    public const string MEDIA_COLLECTION_NAME = 'riders';

    protected $casts = [
        self::COLUMN_STATUS => RiderStatusEnum::class,
        self::COLUMN_OTP_EXPIRES_AT => 'timestamp',
        self::COLUMN_LATITUDE => 'decimal:8',
        self::COLUMN_LONGITUDE => 'decimal:8',
        self::COLUMN_LAST_LOCATION_UPDATE => 'timestamp',
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

    public function latitude(): Attribute
    {
        // TODO: remove this function before production
        return new Attribute(
            get: fn ($value) => is_null($value) ? 29.353325 : (float) $value
        );
    }

    public function longitude(): Attribute
    {
        // TODO: remove this function before production
        return new Attribute(
            get: fn ($value) => is_null($value) ? 47.98227 : (float) $value
        );
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
}
