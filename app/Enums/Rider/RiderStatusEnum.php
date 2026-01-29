<?php

declare(strict_types=1);

namespace App\Enums\Rider;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RiderStatusEnum: string implements HasColor, HasLabel
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case BUSY = 'busy';
    case DELETED = 'deleted';

    public function getLabel(): ?string
    {
        return trans('riders.admin.statuses.' . $this->value);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::ONLINE => 'success',
            self::OFFLINE => 'gray',
            self::BUSY => 'warning',
            self::DELETED => 'danger',
        };
    }

    public static function getDefault(): self
    {
        return self::OFFLINE;
    }
}
