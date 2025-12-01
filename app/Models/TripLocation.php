<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Observers\TripLocationObserver;
use App\Traits\Model\Aggregates\TripLocationAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TripLocationObserver::class)]
class TripLocation extends Model
{
    use HasDefaultColumnModelTrait;
    use SoftDeletes;
    use TripLocationAggregate;

    public const string COLUMN_TRIP_ID = 'trip_id';

    public const string COLUMN_LOCATION_TITLE = 'location_title';

    public const string COLUMN_LOCATION_SUB_TITLE = 'location_sub_title';

    public const string COLUMN_LATITUDE = 'latitude';

    public const string COLUMN_LONGITUDE = 'longitude';

    public const string COLUMN_TYPE = 'type';

    public const string COLUMN_SEQUENCE = 'sequence';

    public const string COLUMN_STATUS = 'status';

    protected function casts(): array
    {
        return [
            self::COLUMN_TYPE => TripLocationTypeEnum::class,
            self::COLUMN_STATUS => TripLocationStatusEnum::class,
        ];
    }

    protected $attributes = [
        self::COLUMN_STATUS => TripLocationStatusEnum::PENDING,
    ];

    /**
     * Check if location is pending
     */
    public function isPending(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::PENDING;
    }

    /**
     * Check if location is arrived
     */
    public function isArrived(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::ARRIVED;
    }

    /**
     * Check if location is picked up
     */
    public function isPickedUp(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::PICKED_UP;
    }

    /**
     * Check if location is completed
     */
    public function isCompleted(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::COMPLETED;
    }

    /**
     * Check if location is dropped off
     */
    public function isDroppedOff(): bool
    {
        return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::DROPPED_OFF;
    }

    /**
     * Check if this is an origin location
     */
    public function isOrigin(): bool
    {
        return $this->{self::COLUMN_TYPE} === TripLocationTypeEnum::ORIGIN;
    }

    /**
     * Check if this is a destination location
     */
    public function isDestination(): bool
    {
        return $this->{self::COLUMN_TYPE} === TripLocationTypeEnum::DESTINATION;
    }

    /**
     * Check if location is finished (fully completed and ready to move to next location)
     * - Origin locations are finished when status is PICKED_UP
     * - Destination locations are finished when status is COMPLETED or DROPPED_OFF
     */
    public function isFinished(): bool
    {
        if ($this->isOrigin()) {
            return $this->{self::COLUMN_STATUS} === TripLocationStatusEnum::PICKED_UP;
        }

        return in_array($this->{self::COLUMN_STATUS}, [
            TripLocationStatusEnum::DROPPED_OFF,
            TripLocationStatusEnum::COMPLETED,
        ]);
    }
}
