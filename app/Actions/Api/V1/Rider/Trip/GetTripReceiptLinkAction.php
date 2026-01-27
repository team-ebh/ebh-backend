<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\DTOs\Api\V1\Rider\Trip\GetTripReceiptLinkDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Rider\TripNotBelongToRiderException;
use App\Exceptions\Trip\TripNotCompletedException;
use App\Models\Trip;
use Illuminate\Support\Facades\URL;

class GetTripReceiptLinkAction
{
    /**
     * Execute the action
     *
     * @throws \Throwable
     */
    public function __invoke(GetTripReceiptLinkDTO $dto): array
    {
        return safeProcess()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($dto) {
                $trip = $dto->trip;

                throw_if(
                    $trip->{Trip::COLUMN_RIDER_ID} !== $dto->riderId,
                    TripNotBelongToRiderException::class
                );

                throw_if(
                    $trip->{Trip::COLUMN_STATUS} !== TripStatusEnum::COMPLETED,
                    TripNotCompletedException::class
                );

                // Generate temporary signed URL (valid for 10 minutes)
                $link = URL::temporarySignedRoute(
                    'v1.riders.trip-history.download-receipt',
                    now()->addMinutes(10),
                    ['trip' => $trip->{Trip::COLUMN_ID}]
                );

                return compact('link');
            });
    }
}
