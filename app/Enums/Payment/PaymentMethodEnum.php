<?php

declare(strict_types=1);

namespace App\Enums\Payment;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: int implements HasDescription, HasIcon, HasLabel
{
    case KNET = 1;
    case CASH = 2;

    public function getLabel(): ?string
    {
        return trans('payments.api.payment_methods.' . $this->name);
    }

    public function getDescription(): ?string
    {
        return trans('payments.api.payment_methods_description.' . $this->name);
    }

    public function getGatewayKey(): ?string
    {
        return match ($this) {
            self::KNET => 'knet',
            default => null,
        };
    }

    public function getIcon(): string | BackedEnum | null
    {
        return match ($this) {
            self::KNET => asset('images/payment_methods/card.png'),
            self::CASH => asset('images/payment_methods/cash.png'),
        };
    }
}
