<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Trip;

use App\DTOs\Api\V1\Customer\Trip\GetTripReceiptLinkDTO;
use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripNotCompletedException;
use App\Exceptions\Trip\TripNotFoundException;
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
                    $trip->{Trip::COLUMN_CUSTOMER_ID} !== $dto->customerId,
                    TripNotFoundException::class
                );

                throw_if(
                    $trip->{Trip::COLUMN_STATUS} !== TripStatusEnum::COMPLETED,
                    TripNotCompletedException::class
                );

                // Generate temporary signed URL (valid for 10 minutes)
                $link = URL::temporarySignedRoute(
                    'v1.customers.trip-history.download-receipt',
                    now()->addMinutes(10),
                    ['trip' => $trip->{Trip::COLUMN_ID}]
                );

                return compact('link');
            });
    }
}
