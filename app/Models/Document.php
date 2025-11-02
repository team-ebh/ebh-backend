<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\Aggregates\DocumentAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasEnabledTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use DocumentAggregate;
    use HasDefaultColumnModelTrait;
    use HasEnabledTrait;
    use HasFactory;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_DESCRIPTION = 'description';

    public const string COLUMN_VALIDITY_PERIOD = 'validity_period';

    public const string COLUMN_ACCEPTED_FORMATS = 'accepted_formats';

    public const string COLUMN_IS_REQUIRED = 'is_required';

    protected $casts = [
        self::COLUMN_ACCEPTED_FORMATS => 'array',
        self::COLUMN_IS_REQUIRED => 'boolean',
    ];
}
