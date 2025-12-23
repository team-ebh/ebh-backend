<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Traits\Model\Aggregates\PaymentAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasDefaultColumnModelTrait;
    use PaymentAggregate;

    public const string COLUMN_PAYMENT_NUMBER = 'payment_number';

    public const string COLUMN_CUSTOMER_ID = 'customer_id';

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_GATEWAY_REFERENCE_ID = 'gateway_reference_id';

    public const string COLUMN_GATEWAY = 'gateway';

    public const string COLUMN_LINK = 'link';

    public const string COLUMN_EXPIRES_AT = 'expires_at';

    public const string COLUMN_ATTEMPT = 'attempt';

    public const string COLUMN_AMOUNT = 'amount';

    public const string COLUMN_CURRENCY = 'currency';

    protected function casts(): array
    {
        return [
            self::COLUMN_GATEWAY => PaymentGatewayEnum::class,
            self::COLUMN_STATUS => PaymentStatusEnum::class,
            self::COLUMN_CURRENCY => CurrencyEnum::class,
            self::COLUMN_EXPIRES_AT => 'datetime',
        ];
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::PENDING;
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::PAID;
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::FAILED;
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::EXPIRED;
    }

    /**
     * Check if payment is locked
     */
    public function isLocked(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::LOCKED;
    }

    /**
     * Check if payment is locked paid
     */
    public function isLockedPaid(): bool
    {
        return $this->{self::COLUMN_STATUS} === PaymentStatusEnum::LOCKED_PAID;
    }

    /**
     * Check if payment has been processed (not pending or locked)
     */
    public function isProcessed(): bool
    {
        return ! in_array($this->{self::COLUMN_STATUS}, [
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::LOCKED,
        ], true);
    }

    /**
     * Check if payment can be processed (is pending or locked)
     */
    public function canBeProcessed(): bool
    {
        return $this->isPending() || $this->isLocked();
    }

    public function isForCustomer(int $customerId): bool
    {
        return $this->{Payment::COLUMN_CUSTOMER_ID} === $customerId;
    }
}
