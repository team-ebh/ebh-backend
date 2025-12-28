<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Payment\PaymentLogTypeEnum;
use App\Traits\Model\Aggregates\PaymentLogAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasDefaultColumnModelTrait;
    use PaymentLogAggregate;

    public const string COLUMN_PAYMENT_ID = 'payment_id';

    public const string COLUMN_TYPE = 'type';

    public const string COLUMN_METHOD = 'method';

    public const string COLUMN_URL = 'url';

    public const string COLUMN_REQUEST_HEADERS = 'request_headers';

    public const string COLUMN_REQUEST_BODY = 'request_body';

    public const string COLUMN_RESPONSE_HEADERS = 'response_headers';

    public const string COLUMN_RESPONSE_BODY = 'response_body';

    public const string COLUMN_STATUS_CODE = 'status_code';

    public const string COLUMN_RESPONSE_TIME = 'response_time';

    public const string COLUMN_ERROR = 'error';

    protected $casts = [
        self::COLUMN_TYPE => PaymentLogTypeEnum::class,
        self::COLUMN_REQUEST_HEADERS => 'array',
        self::COLUMN_REQUEST_BODY => 'array',
        self::COLUMN_RESPONSE_HEADERS => 'array',
        self::COLUMN_RESPONSE_BODY => 'array',
    ];
}
