<?php

declare(strict_types=1);

namespace App\Enums\Payment;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Payment Status Enum
 *
 * Represents the status of a payment transaction
 */
enum PaymentStatusEnum: int implements HasColor, HasIcon, HasLabel
{
    case PENDING = 1;
    case PAID = 2;
    case FAILED = 3;
    case EXPIRED = 4;

    public function getLabel(): ?string
    {
        return trans('payments.statuses.' . $this->name);
    }

    /**
     * Get frontend-friendly label for payment status
     *
     * Returns user-friendly labels:
     * - PAID -> "Success"
     * - FAILED -> "Failure"
     * - PENDING -> "Pending"
     * - EXPIRED -> "Expired"
     */
    public function getFrontendLabel(): ?string
    {
        return trans('payments.frontend_statuses.' . $this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::FAILED => 'danger',
            self::EXPIRED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PAID => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
            self::EXPIRED => 'heroicon-o-calendar',
        };
    }

    /**
     * Get deeplink result parameter based on payment status
     *
     * Returns 'success' for paid payments, 'error' for others
     */
    public function getDeeplinkResult(): string
    {
        return $this === self::PAID ? 'success' : 'failed';
    }

    /**
     * Build deeplink URL for payment result
     */
    public function buildDeeplink(string $baseUrl, int $tripId, string | int $paymentNumber): string
    {
        $result = $this->getDeeplinkResult();

        return "{$baseUrl}?result={$result}&trip_id={$tripId}&payment_number={$paymentNumber}";
    }

    public static function getPaymentStatus(?string $gatewayStatus): self
    {
        $gatewayStatus = strtolower($gatewayStatus ?: '');

        return match ($gatewayStatus) {
            'captured' => self::PAID,
            default => self::FAILED,
        };
    }
}
