<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\RiderStatusEnum;
use App\Traits\Model\Aggregates\RiderStatusLogAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class RiderStatusLog extends Model
{
    use HasDefaultColumnModelTrait;
    use RiderStatusLogAggregate;

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_CHANGED_BY_TYPE = 'changed_by_type';

    public const string COLUMN_CHANGED_BY_ID = 'changed_by_id';

    protected function casts(): array
    {
        return [
            self::COLUMN_STATUS => RiderStatusEnum::class,
        ];
    }
}
