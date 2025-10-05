<?php

declare(strict_types=1);

namespace App\Enums\User;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AdminStatusEnum: int implements HasColor, HasLabel
{
    case ACTIVE = 1;
    case BLOCK = 2;

    public function getLabel(): ?string
    {
        return trans('users.api.status.' . $this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::BLOCK => 'danger',
        };
    }
}
