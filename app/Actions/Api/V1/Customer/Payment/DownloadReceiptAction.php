<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Customer\Payment;

use App\Exceptions\PaymentNotFoundException;
use App\Exceptions\PaymentNotPaidException;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DownloadReceiptAction
{
    /**
     * Execute the action
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

                $payment->load([
                    'order:id,customer_id,payment_method,total_price,currency,created_at,updated_at',
                    'order.customer:id,first_name,last_name,email,phone_number',
                    'order.trips:id,order_id,customer_id,rider_id,total_price,accessibility_price,waiting_price,currency,created_at,updated_at',
                    'order.trips.rider:id,full_name,phone_number',
                    'order.trips.locations:id,trip_id,location_title,location_sub_title,latitude,longitude,type,sequence,status',
                ]);

                $filename = 'EBH-' . $payment->{Payment::COLUMN_PAYMENT_NUMBER} . '-' . now()->timestamp . '.pdf';

                return Pdf::loadView('pdf.trip-receipt', compact('payment'))->download($filename);
            });
    }
}
