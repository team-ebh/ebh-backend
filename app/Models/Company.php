<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CompanyObserver;
use App\Traits\Model\Aggregates\CompanyAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasEnabledTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(CompanyObserver::class)]
class Company extends Model
{
    use CompanyAggregate;
    use HasDefaultColumnModelTrait;
    use HasEnabledTrait;
    use HasFactory;
    use LogsActivity;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_ADDRESS = 'address';

    public const string COLUMN_COMMISSION_RATE = 'commission_rate';

    public const string COLUMN_IS_CUSTOM_RATE = 'is_custom_rate';

    protected $casts = [
        self::COLUMN_COMMISSION_RATE => 'decimal:2',
        self::COLUMN_IS_CUSTOM_RATE => 'boolean',
    ];

    /**
     * Check if company has custom commission rate
     */
    public function hasCustomRate(): bool
    {
        return $this->{self::COLUMN_IS_CUSTOM_RATE} === true;
    }
}
