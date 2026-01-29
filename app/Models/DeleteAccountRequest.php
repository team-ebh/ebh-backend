<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeleteAccountRequest extends Model
{
    use HasDefaultColumnModelTrait;

    public const string COLUMN_REQUESTABLE_TYPE = 'requestable_type';

    public const string COLUMN_REQUESTABLE_ID = 'requestable_id';

    public const string COLUMN_SECURITY_TOKEN = 'security_token';

    public const string COLUMN_SECURITY_TOKEN_EXPIRES_AT = 'security_token_expires_at';

    public const string COLUMN_ACCOUNT_DELETED_AT = 'account_deleted_at';

    protected function casts(): array
    {
        return [
            self::COLUMN_SECURITY_TOKEN_EXPIRES_AT => 'datetime',
            self::COLUMN_ACCOUNT_DELETED_AT => 'datetime',
        ];
    }

    /**
     * Get the owning requestable model (Customer or Rider)
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if security token is valid
     */
    public function isSecurityTokenValid(): bool
    {
        return $this->{self::COLUMN_SECURITY_TOKEN} !== null
            && $this->{self::COLUMN_SECURITY_TOKEN_EXPIRES_AT} !== null
            && $this->{self::COLUMN_SECURITY_TOKEN_EXPIRES_AT}->isFuture();
    }
}
