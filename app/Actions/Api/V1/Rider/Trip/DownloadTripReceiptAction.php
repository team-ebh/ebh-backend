<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Trip;

use App\Enums\Trip\TripStatusEnum;
use App\Exceptions\Trip\TripNotCompletedException;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DownloadTripReceiptAction
{
    /**
     * Execute the action
     *
     * This action is called after signed URL validation, so we don't need to verify ownership.
     * We only verify the trip is completed.
     *
     * @throws \Throwable
     */
    public function __invoke(Trip $trip): Response
    {
        return safeProcess()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($trip) {
                // Verify trip is completed
                throw_if(
                    $trip->{Trip::COLUMN_STATUS} !== TripStatusEnum::COMPLETED,
                    TripNotCompletedException::class
                );

                // Load necessary relationships
                $trip->load([
                    'customer:id,first_name,last_name,phone_number',
                    'rider:id,full_name,phone_number',
                    'locations:id,trip_id,location_title,location_sub_title,latitude,longitude,type,sequence,status',
                    'order:id,customer_id,payment_method,total_price,currency,created_at,updated_at',
                    'order.paidPayment:id,order_id,payment_number,amount,currency,status',
                ]);

                $filename = 'EBH-TRIP-' . tripNumberFormat($trip) . '-' . now()->timestamp . '.pdf';

                return Pdf::loadView('pdf.trip-receipt-by-trip', compact('trip'))->download($filename);
            });
    }
}
