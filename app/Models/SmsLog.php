<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SMS\SmsProvidersEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsLog extends Model
{
    use HasDefaultColumnModelTrait;

    public const string COLUMN_RECEIVABLE_TYPE = 'receivable_type';

    public const string COLUMN_RECEIVABLE_ID = 'receivable_id';

    public const string COLUMN_REQUEST_DATA = 'request_data';

    public const string COLUMN_PROVIDER_RESPONSE = 'provider_response';

    public const string COLUMN_RECIPIENT_NUMBER = 'recipient_number';

    public const string COLUMN_SMS_TYPE = 'sms_type';

    public const string COLUMN_SMS_PROVIDER = 'sms_provider';

    public const string COLUMN_MESSAGE = 'message';

    public const string COLUMN_SENT_AT = 'sent_at';

    public const string COLUMN_IS_SUCCESSFUL = 'is_successful';

    public const string COLUMN_STATUS_CODE = 'status_code';

    protected function casts(): array
    {
        return [
            self::COLUMN_REQUEST_DATA => 'json',
            self::COLUMN_PROVIDER_RESPONSE => 'json',
            self::COLUMN_SMS_TYPE => SmsTypesEnum::class,
            self::COLUMN_SMS_PROVIDER => SmsProvidersEnum::class,
            self::COLUMN_SENT_AT => 'timestamp',
            self::COLUMN_IS_SUCCESSFUL => 'boolean',
        ];
    }

    public function receivable(): MorphTo
    {
        return $this->morphTo();
    }
}
