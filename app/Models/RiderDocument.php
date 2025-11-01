<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\Aggregates\RiderDocumentAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasMediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class RiderDocument extends Model implements HasMedia
{
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use HasMediaTrait;
    use RiderDocumentAggregate;

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_DOCUMENT_ID = 'document_id';

    public const string COLUMN_EXPIRES_AT = 'expires_at';

    protected $casts = [
        self::COLUMN_EXPIRES_AT => 'datetime',
    ];
}
