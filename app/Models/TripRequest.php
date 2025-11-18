<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripRequestStatusEnum;
use App\Traits\Model\Aggregates\TripRequestAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;

class TripRequest extends Model
{
    use HasDefaultColumnModelTrait;
    use TripRequestAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_RIDER_ID = 'rider_id';

    public const string COLUMN_DISTANCE_METERS = 'distance_meters';

    public const string COLUMN_ESTIMATED_ARRIVAL_MINUTES = 'estimated_arrival_minutes';

    public const string COLUMN_STATUS = 'status';

    public const string COLUMN_SENT_AT = 'sent_at';

    public const string COLUMN_RESPONDED_AT = 'responded_at';

    public const string COLUMN_DECLINE_REASON = 'decline_reason';

    public const string COLUMN_PRIORITY = 'priority';

    public const string COLUMN_SEARCH_RADIUS_METERS = 'search_radius_meters';

    public const string COLUMN_SEARCH_ATTEMPT = 'search_attempt';

    public const string COLUMN_EXPIRES_AT = 'expires_at';

    protected $casts = [
        self::COLUMN_STATUS => TripRequestStatusEnum::class,
        self::COLUMN_SENT_AT => 'timestamp',
        self::COLUMN_RESPONDED_AT => 'timestamp',
        self::COLUMN_EXPIRES_AT => 'timestamp',
        self::COLUMN_DISTANCE_METERS => 'integer',
        self::COLUMN_ESTIMATED_ARRIVAL_MINUTES => 'integer',
    ];

    protected $attributes = [self::COLUMN_STATUS => TripRequestStatusEnum::PENDING];

    #[Scope]
    protected function pending($query)
    {
        return $query->where(self::COLUMN_STATUS, TripRequestStatusEnum::PENDING);
    }

    #[Scope]
    protected function accepted($query)
    {
        return $query->where(self::COLUMN_STATUS, TripRequestStatusEnum::ACCEPTED);
    }

    #[Scope]
    protected function forRider($query, int $riderId)
    {
        return $query->where(self::COLUMN_RIDER_ID, $riderId);
    }

    #[Scope]
    protected function forTrip($query, int $tripId)
    {
        return $query->where(self::COLUMN_TRIP_ID, $tripId);
    }

    #[Scope]
    protected function notExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull(self::COLUMN_EXPIRES_AT)
                ->orWhere(self::COLUMN_EXPIRES_AT, '>', now());
        });
    }

    #[Scope]
    protected function expired($query)
    {
        return $query->whereNotNull(self::COLUMN_EXPIRES_AT)
            ->where(self::COLUMN_EXPIRES_AT, '<=', now())
            ->where(self::COLUMN_STATUS, TripRequestStatusEnum::PENDING);
    }

    /**
     * Check if trip request belongs to rider
     */
    public function belongsToRider(int $riderId): bool
    {
        return $this->{self::COLUMN_RIDER_ID} === $riderId;
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripRequestStatusEnum::PENDING;
    }

    /**
     * Check if request is accepted
     */
    public function isAccepted(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripRequestStatusEnum::ACCEPTED;
    }

    /**
     * Check if request is declined
     */
    public function isDeclined(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripRequestStatusEnum::DECLINED;
    }

    /**
     * Check if request is expired
     */
    public function isExpired(): bool
    {
        if ($this->{self::COLUMN_STATUS} !== TripRequestStatusEnum::PENDING) {
            return false;
        }

        if ($this->{self::COLUMN_EXPIRES_AT} === null) {
            return false;
        }

        return $this->{self::COLUMN_EXPIRES_AT} <= now();
    }

    /**
     * Mark request as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            self::COLUMN_STATUS => TripRequestStatusEnum::EXPIRED,
            self::COLUMN_RESPONDED_AT => now(),
        ]);
    }

    /**
     * Mark request as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update([
            self::COLUMN_STATUS => TripRequestStatusEnum::ACCEPTED,
            self::COLUMN_RESPONDED_AT => now(),
        ]);
    }

    /**
     * Mark request as declined
     */
    public function markAsDeclined(string $reason): void
    {
        $this->update([
            self::COLUMN_STATUS => TripRequestStatusEnum::DECLINED,
            self::COLUMN_DECLINE_REASON => $reason,
            self::COLUMN_RESPONDED_AT => now(),
        ]);
    }

    /**
     * Mark request as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            self::COLUMN_STATUS => TripRequestStatusEnum::CANCELLED,
            self::COLUMN_RESPONDED_AT => now(),
        ]);
    }
}
