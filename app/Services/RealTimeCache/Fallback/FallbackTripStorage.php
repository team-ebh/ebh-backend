<?php

declare(strict_types=1);

namespace App\Services\RealTimeCache\Fallback;

use App\Services\RealTimeCache\Contracts\TripStorageInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback Trip Storage
 *
 * Tries Redis first, falls back to Database cache if Redis fails.
 */
class FallbackTripStorage implements TripStorageInterface
{
    public function __construct(
        private readonly TripStorageInterface $primary,
        private readonly TripStorageInterface $fallback,
        private readonly RedisHealthChecker $healthChecker,
    ) {}

    public function store(int $tripId, array $data, string $status = 'pending'): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->store($tripId, $data, $status);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'store');
            }
        }

        $this->fallback->store($tripId, $data, $status);
    }

    public function get(int $tripId): ?array
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->get($tripId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'get');
            }
        }

        return $this->fallback->get($tripId);
    }

    public function update(int $tripId, array $data): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->update($tripId, $data);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'update');
            }
        }

        $this->fallback->update($tripId, $data);
    }

    public function updateStatus(int $tripId, string $status): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->updateStatus($tripId, $status);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'updateStatus');
            }
        }

        $this->fallback->updateStatus($tripId, $status);
    }

    public function remove(int $tripId): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->remove($tripId);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'remove');
            }
        }

        $this->fallback->remove($tripId);
    }

    public function exists(int $tripId): bool
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->exists($tripId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'exists');
            }
        }

        return $this->fallback->exists($tripId);
    }

    public function setRiderActiveTrip(int $riderId, int $tripId): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setRiderActiveTrip($riderId, $tripId);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setRiderActiveTrip');
            }
        }

        $this->fallback->setRiderActiveTrip($riderId, $tripId);
    }

    public function getRiderActiveTrip(int $riderId): ?int
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->getRiderActiveTrip($riderId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getRiderActiveTrip');
            }
        }

        return $this->fallback->getRiderActiveTrip($riderId);
    }

    public function clearRiderActiveTrip(int $riderId): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->clearRiderActiveTrip($riderId);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'clearRiderActiveTrip');
            }
        }

        $this->fallback->clearRiderActiveTrip($riderId);
    }

    public function setCustomerActiveTrip(int $customerId, int $tripId): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->setCustomerActiveTrip($customerId, $tripId);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'setCustomerActiveTrip');
            }
        }

        $this->fallback->setCustomerActiveTrip($customerId, $tripId);
    }

    public function getCustomerActiveTrip(int $customerId): ?int
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->getCustomerActiveTrip($customerId);
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getCustomerActiveTrip');
            }
        }

        return $this->fallback->getCustomerActiveTrip($customerId);
    }

    public function clearCustomerActiveTrip(int $customerId): void
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                $this->primary->clearCustomerActiveTrip($customerId);

                return;
            } catch (Throwable $e) {
                $this->handleFailure($e, 'clearCustomerActiveTrip');
            }
        }

        $this->fallback->clearCustomerActiveTrip($customerId);
    }

    public function getAllTripIds(): array
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->getAllTripIds();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'getAllTripIds');
            }
        }

        return $this->fallback->getAllTripIds();
    }

    public function count(): int
    {
        if ($this->healthChecker->isHealthy()) {
            try {
                return $this->primary->count();
            } catch (Throwable $e) {
                $this->handleFailure($e, 'count');
            }
        }

        return $this->fallback->count();
    }

    public function flush(): void
    {
        try {
            $this->primary->flush();
        } catch (Throwable) {
            // Ignore
        }

        $this->fallback->flush();
    }

    private function handleFailure(Throwable $e, string $method): void
    {
        $this->healthChecker->markUnhealthy();

        Log::warning('RealTimeCache: Redis trip storage failed, using database fallback', [
            'method' => $method,
            'error' => $e->getMessage(),
        ]);
    }
}
