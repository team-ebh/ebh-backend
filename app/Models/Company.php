<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasEnabledTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasDefaultColumnModelTrait;
    use HasEnabledTrait;
    use HasFactory;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_ADDRESS = 'address';

    public const string COLUMN_COMMISSION_RATE = 'commission_rate';

    protected $casts = [
        self::COLUMN_COMMISSION_RATE => 'decimal:2',
    ];

    public function riders(): HasMany
    {
        return $this->hasMany(Rider::class, Rider::COLUMN_COMPANY_ID);
    }
}
