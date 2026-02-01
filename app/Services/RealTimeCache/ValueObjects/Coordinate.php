<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\ValueObjects;

/**
 * Coordinate Value Object
 *
 * Immutable representation of a geographic coordinate (latitude, longitude)
 */
readonly class Coordinate
{
    private function __construct(
        private float $latitude,
        private float $longitude,
    ) {}

    /**
     * Create a new Coordinate instance
     */
    public static function make(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    /**
     * Create from array
     *
     * @param  array{lat: float, lng: float}|array{latitude: float, longitude: float}  $data
     */
    public static function fromArray(array $data): self
    {
        $lat = $data['lat'] ?? $data['latitude'] ?? 0.0;
        $lng = $data['lng'] ?? $data['longitude'] ?? 0.0;

        return new self((float) $lat, (float) $lng);
    }

    /**
     * Create from string "lat,lng"
     */
    public static function fromString(string $value): self
    {
        $parts = explode(',', $value);

        if (count($parts) !== 2) {
            return new self(0.0, 0.0);
        }

        return new self((float) trim($parts[0]), (float) trim($parts[1]));
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function lat(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function lng(): float
    {
        return $this->longitude;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }

    /**
     * Format: "lat,lng"
     */
    public function toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Check if coordinate is valid (non-zero)
     */
    public function isValid(): bool
    {
        return $this->latitude !== 0.0 || $this->longitude !== 0.0;
    }

    /**
     * Calculate distance to another coordinate in meters using Haversine formula
     */
    public function distanceTo(Coordinate $other): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
