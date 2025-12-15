<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Traits\Model\Aggregates\RiderAccessibilityCertificationAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiderAccessibilityCertification extends Model
{
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use RiderAccessibilityCertificationAggregate;

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_CERTIFICATION_TYPE = 'certification_type';

    protected $casts = [
        self::COLUMN_CERTIFICATION_TYPE => AccessibilityCertificationEnum::class,
    ];
}
