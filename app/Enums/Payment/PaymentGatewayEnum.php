<?php

declare(strict_types=1);

namespace App\Enums\Payment;

use Filament\Support\Contracts\HasLabel;

/**
 * Payment Gateway Enum
 *
 * Represents available payment gateway providers
 */
enum PaymentGatewayEnum: string implements HasLabel
{
    case UPAYMENTS = 'upayments';
    case MYFATOORAH = 'myfatoorah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UPAYMENTS => 'UPayments',
            self::MYFATOORAH => 'MyFatoorah',
        };
    }
}
