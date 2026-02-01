<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\ValueObjects;

/**
 * Distance Value Object
 *
 * Immutable representation of a distance with unit conversion support
 */
readonly class Distance
{
    private function __construct(
        private float $meters,
    ) {}

    /**
     * Create distance in meters
     */
    public static function meters(float | int $value): self
    {
        return new self((float) $value);
    }

    /**
     * Create distance in kilometers
     */
    public static function kilometers(float | int $value): self
    {
        return new self((float) $value * 1000);
    }

    /**
     * Get distance in meters
     */
    public function toMeters(): float
    {
        return $this->meters;
    }

    /**
     * Get distance in meters as integer
     */
    public function toMetersInt(): int
    {
        return (int) round($this->meters);
    }

    /**
     * Get distance in kilometers
     */
    public function toKilometers(): float
    {
        return $this->meters / 1000;
    }

    /**
     * Get raw value in meters
     */
    public function value(): float
    {
        return $this->meters;
    }

    /**
     * Check if this distance is greater than another
     */
    public function greaterThan(Distance $other): bool
    {
        return $this->meters > $other->meters;
    }

    /**
     * Check if this distance is less than another
     */
    public function lessThan(Distance $other): bool
    {
        return $this->meters < $other->meters;
    }

    /**
     * Check if this distance is within a limit
     */
    public function isWithin(Distance $limit): bool
    {
        return $this->meters <= $limit->meters;
    }

    /**
     * Format distance for display
     */
    public function format(): string
    {
        if ($this->meters >= 1000) {
            return round($this->toKilometers(), 1) . ' km';
        }

        return round($this->meters) . ' m';
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
