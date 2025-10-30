<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasDefaultColumnModelTrait;
    use HasFactory;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_ADDRESS = 'address';

    public const string COLUMN_COMMISSION_RATE = 'commission_rate';

    public const string COLUMN_ENABLED = 'enabled';

    protected $casts = [
        self::COLUMN_COMMISSION_RATE => 'decimal:2',
    ];
}
