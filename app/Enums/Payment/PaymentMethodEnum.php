<?php

declare(strict_types=1);

namespace App\Enums\Payment;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: int implements HasLabel
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
}
