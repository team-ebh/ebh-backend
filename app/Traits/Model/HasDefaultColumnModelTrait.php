<?php

declare(strict_types=1);

namespace App\Traits\Model;

trait HasDefaultColumnModelTrait
{
    public const string COLUMN_ID = 'id';

    public const string COLUMN_CREATED_AT = 'created_at';

    public const string COLUMN_UPDATED_AT = 'updated_at';

    public static function getTableName(): string
    {
        return (new self)->getTable();
    }
}
