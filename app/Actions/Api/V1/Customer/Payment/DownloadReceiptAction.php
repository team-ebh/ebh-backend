<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\Exceptions\PaymentNotFoundException;
use App\Exceptions\PaymentNotPaidException;
use App\Exceptions\Trip\TripNotFoundException;
use App\Models\Payment;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DownloadReceiptAction
{
    /**
     * Execute the action
     *
     * Uses the trip-based PDF view for consistent receipt formatting across both
     * payment and trip history APIs.
     *
     * @throws \Throwable
     */
    public function __invoke(Payment $payment): Response
    {
        return safeProcess()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($payment) {
                if (auth('customer')->id()) {
                    throw_if(
                        ! $payment->isForCustomer(auth('customer')->id()),
                        PaymentNotFoundException::class
                    );
                } else {
                    abort(403);
                }

                throw_if(
                    ! $payment->isPaid(),
                    PaymentNotPaidException::class
                );

                // Load the order with its first trip
                $payment->load(['order.trips']);

                /** @var Trip|null $trip */
                $trip = $payment->order?->trips?->first();

                throw_if($trip === null, TripNotFoundException::class);

                // Load necessary relationships for the trip-based PDF
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
