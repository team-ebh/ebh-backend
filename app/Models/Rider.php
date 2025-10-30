<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\RiderStatusEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rider extends Model
{
    use HasDefaultColumnModelTrait;
    use HasFactory;

    public const string COLUMN_FULL_NAME = 'full_name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_COMPANY_ID = 'company_id';

    public const string COLUMN_STATUS = 'status';

    protected $casts = [
        self::COLUMN_STATUS => RiderStatusEnum::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, self::COLUMN_COMPANY_ID);
    }
}
