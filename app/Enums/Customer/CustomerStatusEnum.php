<?php

declare(strict_types=1);

namespace App\Enums\Customer;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerStatusEnum: int implements HasColor, HasLabel
{
    case PENDING_VERIFICATION = 1;
    case ACTIVE = 2;

    public function getLabel(): ?string
    {
        return trans('customers.api.status.' . $this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING_VERIFICATION => 'warning',
            self::ACTIVE => 'success',
        };
    }
}
