<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Drivers\Database;

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use App\Services\RealTimeCache\Contracts\StatusStorageInterface;

/**
 * Database Status Storage (Fallback)
 *
 * Uses the riders table status column when Redis is unavailable.
 */
class DatabaseStatusStorage implements StatusStorageInterface
{
    public function setStatus(int $riderId, string $status, array $meta = [], ?int $ttl = null): void
    {
        $enumStatus = $this->mapToEnum($status);

        Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->update([
                Rider::COLUMN_STATUS => $enumStatus,
            ]);
    }

    public function getStatus(int $riderId): ?string
    {
        $rider = Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->first([Rider::COLUMN_STATUS]);

        if (! $rider) {
            return null;
        }

        $status = $rider->{Rider::COLUMN_STATUS};

        if ($status === RiderStatusEnum::OFFLINE || $status === RiderStatusEnum::DELETED) {
            return null;
        }

        return $this->mapFromEnum($status);
    }

    public function getMeta(int $riderId): ?array
    {
        $rider = Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->first([Rider::COLUMN_STATUS, Rider::COLUMN_LAST_LOCATION_UPDATE]);

        if (! $rider) {
            return null;
        }

        return [
            'status' => $this->mapFromEnum($rider->{Rider::COLUMN_STATUS}),
            'last_seen' => $rider->{Rider::COLUMN_LAST_LOCATION_UPDATE}?->timestamp,
        ];
    }

    public function heartbeat(int $riderId, ?int $ttl = null): bool
    {
        $affected = Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::OFFLINE)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::DELETED)
            ->update([
                Rider::COLUMN_LAST_LOCATION_UPDATE => now(),
            ]);

        return $affected > 0;
    }

    public function setOnline(int $riderId, array $meta = []): void
    {
        $this->setStatus($riderId, self::STATUS_ONLINE, $meta);
    }

    public function setBusy(int $riderId, array $meta = []): void
    {
        $this->setStatus($riderId, self::STATUS_BUSY, $meta);
    }

    public function setOffline(int $riderId): void
    {
        Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->update([
                Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
            ]);
    }

    public function isOnline(int $riderId): bool
    {
        return Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::OFFLINE)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::DELETED)
            ->exists();
    }

    public function isBusy(int $riderId): bool
    {
        return Rider::query()
            ->where(Rider::COLUMN_ID, $riderId)
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::BUSY)
            ->exists();
    }

    public function getOnlineRiderIds(): array
    {
        return Rider::query()
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::OFFLINE)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::DELETED)
            ->pluck(Rider::COLUMN_ID)
            ->toArray();
    }

    public function getReadyRiderIds(): array
    {
        return Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::ONLINE)
            ->pluck(Rider::COLUMN_ID)
            ->toArray();
    }

    public function getBusyRiderIds(): array
    {
        return Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::BUSY)
            ->pluck(Rider::COLUMN_ID)
            ->toArray();
    }

    public function countOnline(): int
    {
        return Rider::query()
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::OFFLINE)
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::DELETED)
            ->count();
    }

    public function countReady(): int
    {
        return Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::ONLINE)
            ->count();
    }

    public function countBusy(): int
    {
        return Rider::query()
            ->where(Rider::COLUMN_STATUS, RiderStatusEnum::BUSY)
            ->count();
    }

    public function flush(): void
    {
        Rider::query()
            ->whereNot(Rider::COLUMN_STATUS, RiderStatusEnum::DELETED)
            ->update([
                Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE,
            ]);
    }

    private function mapToEnum(string $status): RiderStatusEnum
    {
        return match ($status) {
            self::STATUS_ONLINE => RiderStatusEnum::ONLINE,
            self::STATUS_BUSY => RiderStatusEnum::BUSY,
            default => RiderStatusEnum::OFFLINE,
        };
    }

    private function mapFromEnum(RiderStatusEnum $status): string
    {
        return match ($status) {
            RiderStatusEnum::ONLINE => self::STATUS_ONLINE,
            RiderStatusEnum::BUSY => self::STATUS_BUSY,
            default => self::STATUS_OFFLINE,
        };
    }
}
