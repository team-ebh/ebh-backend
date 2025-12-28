<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Payment\GetPaymentLink;

use App\Exceptions\Payment\PaymentLinkGenerationException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Trip;
use App\Services\Payment\DTOs\CreatePaymentLinkDTO;
use App\Services\Payment\PaymentService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Generate Payment Link Pipe
 *
 * Builds CreatePaymentLinkDTO from trip and generates payment link
 */
readonly class GeneratePaymentLinkPipe
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @throws \Throwable
     */
    public function handle(PaymentLinkContext $context, Closure $next): mixed
    {
        if ($context->paymentLink) {
            return $next($context);
        }

        $dto = $this->buildPaymentLinkDTO($context->trip, $context->payment);
        $paymentResponse = $this->paymentService->createPaymentLink($dto);

        if (! $paymentResponse->success) {
            Log::critical('Payment gateway failed to generate payment link', [
                'payment_id' => $context->payment->{Payment::COLUMN_ID},
                'trip_id' => $context->trip->{Trip::COLUMN_ID},
                'customer_id' => $context->trip->{Trip::COLUMN_CUSTOMER_ID},
                'error_message' => $paymentResponse->message,
                'error_code' => $paymentResponse->errorCode,
                'raw_response' => $paymentResponse->rawResponse,
            ]);

            throw_if(true, PaymentLinkGenerationException::class);
        }

        $context->paymentLink = $paymentResponse->rawResponse['link'] ?? null;
        $context->gatewayReferenceId = $paymentResponse->rawResponse['trackId'] ?? null;

        return $next($context);
    }

    /**
     * Build payment link DTO from trip and payment data
     */
    private function buildPaymentLinkDTO(Trip $trip, Payment $payment): CreatePaymentLinkDTO
    {
        $dto = new CreatePaymentLinkDTO();

        // Order
        $dto->orderId = tripNumberFormat($trip);
        $dto->orderCurrency = $trip->{Trip::COLUMN_CURRENCY}->value;
        $dto->orderAmount = priceFormat($trip->{Trip::COLUMN_TOTAL_PRICE});

        // Payment Gateway
        $dto->paymentGatewaySrc = $trip->{Trip::COLUMN_PAYMENT_METHOD}->getGatewayKey();

        // Language
        $dto->language = app()->getLocale();

        // Reference
        $dto->referenceId = $payment->{Payment::COLUMN_PAYMENT_NUMBER};

        // Payment ID
        $dto->paymentId = $payment->{Payment::COLUMN_ID};

        // Customer
        $dto->customerName = $trip->customer->getFullName();
        $dto->customerEmail = $trip->customer->{Customer::COLUMN_EMAIL};
        $dto->customerMobile = $trip->customer->getPhoneNumberWithPrefix();

        // URLs
        $dto->returnUrl = config('payment.upayments.callback_url');
        $dto->cancelUrl = config('payment.upayments.callback_url');
        $dto->notificationUrl = config('payment.upayments.webhook_url');

        return $dto;
    }
}
