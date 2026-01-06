<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Observers\TripObserver;
use App\Traits\Model\Aggregates\TripAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TripObserver::class)]
class Trip extends Model
{
    use HasDefaultColumnModelTrait;
    use SoftDeletes;
    use TripAggregate;

    public const string COLUMN_CUSTOMER_ID = 'customer_id';

    public const string COLUMN_ORDER_ID = 'order_id';

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_TRIP_TYPE_ID = 'trip_type_id';

    public const string COLUMN_RIDE_TYPE = 'ride_type';

    public const string COLUMN_VEHICLE_TYPE_ID = 'vehicle_type_id';

    public const string COLUMN_PASSENGER_COUNT = 'passenger_count';

    public const string COLUMN_BASE_FARE = 'base_fare';

    public const string COLUMN_ROUND_TRIP_PRICE = 'round_trip_price';

    public const string COLUMN_ACCESSIBILITY_PRICE = 'accessibility_price';

    public const string COLUMN_WAITING_PRICE = 'waiting_price';

    public const string COLUMN_WAITING_TIME = 'waiting_time';

    public const string COLUMN_TOTAL_PRICE = 'total_price';

    public const string COLUMN_COMMISSION_RATE = 'commission_rate';

    public const string COLUMN_COMMISSION_AMOUNT = 'commission_amount';

    public const string COLUMN_CURRENCY = 'currency';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_DEMAND_TRIP_ID = 'demand_trip_id';

    public const string COLUMN_SCHEDULED_TIME = 'scheduled_time';

    protected function casts(): array
    {
        return [
            self::COLUMN_TRIP_TYPE_ID => TripTypeEnum::class,
            self::COLUMN_RIDE_TYPE => RideTypeEnum::class,
            self::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::class,
            self::COLUMN_CURRENCY => CurrencyEnum::class,
            self::COLUMN_STATUS => TripStatusEnum::class,
            self::COLUMN_SCHEDULED_TIME => 'datetime',
            self::COLUMN_COMMISSION_RATE => 'decimal:2',
            self::COLUMN_COMMISSION_AMOUNT => 'decimal:3',
        ];
    }

    #[Scope]
    protected function forCustomer($query, int $customerId)
    {
        return $query->where(self::COLUMN_CUSTOMER_ID, $customerId);
    }

    #[Scope]
    protected function forRider($query, int $riderId)
    {
        return $query->where(self::COLUMN_RIDER_ID, $riderId);
    }

    #[Scope]
    protected function byStatus($query, TripStatusEnum $status)
    {
        return $query->where(self::COLUMN_STATUS, $status);
    }

    #[Scope]
    protected function activeTrips($query)
    {
        return $query->whereNotIn(self::COLUMN_STATUS, [
            TripStatusEnum::DRAFT,
            TripStatusEnum::COMPLETED,
            TripStatusEnum::CANCELED_BY_CUSTOMER,
            TripStatusEnum::CANCELLED_BY_RIDER,
        ]);
    }

    #[Scope]
    protected function scheduledTrips($query)
    {
        return $query->where(self::COLUMN_STATUS, TripStatusEnum::DRAFT)
            ->where(self::COLUMN_TRIP_TYPE_ID, TripTypeEnum::SCHEDULED);
    }

    #[Scope]
    protected function excludingDraftAndCancelled($query)
    {
        return $query->whereNotIn(self::COLUMN_STATUS, [
            TripStatusEnum::DRAFT,
            TripStatusEnum::CANCELED_BY_CUSTOMER,
            TripStatusEnum::CANCELLED_BY_RIDER,
        ]);
    }

    #[Scope]
    protected function withoutDraft($query)
    {
        return $query->where(self::COLUMN_STATUS, '<>', TripStatusEnum::DRAFT);
    }

    /**
     * Check if trip is draft
     */
    public function isDraft(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::DRAFT;
    }

    /**
     * Check if trip is scheduled type
     */
    public function isScheduledTripType(): bool
    {
        return $this->{self::COLUMN_TRIP_TYPE_ID} === TripTypeEnum::SCHEDULED;
    }

    /**
     * Check if trip is pending rider acceptance
     */
    public function isPendingRider(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::PENDING_RIDER;
    }

    /**
     * Check if trip is accepted by rider
     */
    public function isAcceptedByRider(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::ACCEPTED_RIDER;
    }

    /**
     * Check if trip is arrived
     */
    public function isArrived(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::ARRIVED;
    }

    /**
     * Check if trip is in progress
     */
    public function isInProgress(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::IN_PROGRESS;
    }

    /**
     * Check if trip is completed
     */
    public function isCompleted(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::COMPLETED;
    }

    /**
     * Check if trip was cancelled by customer
     */
    public function isCanceledByCustomer(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::CANCELED_BY_CUSTOMER;
    }

    /**
     * Check if trip was cancelled by rider
     */
    public function isCancelledByRider(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripStatusEnum::CANCELLED_BY_RIDER;
    }

    /**
     * Check if trip belongs to customer
     */
    public function belongsToCustomer(int $customerId): bool
    {
        return $this->{self::COLUMN_CUSTOMER_ID} === $customerId;
    }

    /**
     * Check if trip belongs to rider
     */
    public function belongsToRider(int $riderId): bool
    {
        return $this->{self::COLUMN_RIDER_ID} === $riderId;
    }

    /**
     * Check if trip can be cancelled by rider
     * Only ACCEPTED_RIDER and ARRIVED trips can be cancelled by rider
     */
    public function canBeCancelledByRider(): bool
    {
        return in_array($this->{self::COLUMN_STATUS}, [
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ARRIVED,
        ], true);
    }

    /**
     * Check trip can be cancelled or not
     * Only DRAFT, PENDING_RIDER and ACCEPTED_RIDER trips can be cancelled by customer
     */
    public function canCancelTrip(): bool
    {
        return in_array(
            $this->{self::COLUMN_STATUS},
            [TripStatusEnum::DRAFT, TripStatusEnum::PENDING_RIDER, TripStatusEnum::ACCEPTED_RIDER, TripStatusEnum::ARRIVED]
        );
    }

    /**
     * Check if customer can get rider location
     * Only allowed when trip has an assigned rider and is in specific statuses
     */
    public function canGetRiderLocation(): bool
    {
        // Only these statuses allow location tracking
        return in_array($this->{self::COLUMN_STATUS}, [
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ARRIVED,
            TripStatusEnum::IN_PROGRESS,
        ], true);
    }

    /**
     * Check if trip status information is available
     * Returns true when driver has been assigned and trip is in trackable state
     */
    public function hasTripStatusAvailable(): bool
    {
        return in_array($this->{self::COLUMN_STATUS}, [
            TripStatusEnum::ACCEPTED_RIDER,
            TripStatusEnum::ARRIVED,
            TripStatusEnum::IN_PROGRESS,
            TripStatusEnum::COMPLETED,
        ], true);
    }

    /**
     * Check if order payment method is KNET
     */
    public function isKnetPayment(): bool
    {
        return $this->order?->{$this->order::COLUMN_PAYMENT_METHOD} === PaymentMethodEnum::KNET;
    }

    /**
     * Check if order payment method is CASH
     */
    public function isCashPayment(): bool
    {
        return $this->order?->{$this->order::COLUMN_PAYMENT_METHOD} === PaymentMethodEnum::CASH;
    }

    public function hasPaidPayment(): bool
    {
        if ($this->isCashPayment()) {
            return true;
        }

        // Check if order has paid payment
        return $this->order()
            ->whereHas('paidPayment')
            ->exists();

    }

    public function hasCompletedAndPaidPayment(): bool
    {
        return $this->isCompleted() && $this->hasPaidPayment();
    }

    public function isRoundTripWithWait(): bool
    {
        return $this->{Trip::COLUMN_RIDE_TYPE} === RideTypeEnum::ROUND_TRIP_WAIT;
    }

    public function isRoundTrip(): bool
    {
        return $this->{Trip::COLUMN_RIDE_TYPE} === RideTypeEnum::ROUND_TRIP;
    }
}
