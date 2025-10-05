<?php

declare(strict_types=1);

namespace App\Enums\Currency;

use Filament\Support\Contracts\HasLabel;

enum CurrencyEnum: string implements HasLabel
{
    case KD = 'KD';

    public function getLabel(): ?string
    {
        return trans('currencies.' . $this->name);
    }
}
