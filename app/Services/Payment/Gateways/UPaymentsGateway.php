<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Interfaces\Repositories\Payment\PaymentLogRepositoryInterface;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Services\Payment\Contracts\PaymentGatewayCustomerTokenInterface;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CreateCustomerTokenDTO;
use App\Services\Payment\DTOs\CreatePaymentLinkDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use App\Services\Payment\Traits\LogsPaymentRequests;
use App\Services\Payment\Traits\MakesHttpRequests;

/**
 * UPayments Gateway Implementation
 *
 * Clean, SOLID implementation using composition over inheritance
 * Follows Strategy Pattern for payment gateway
 */
class UPaymentsGateway implements PaymentGatewayCustomerTokenInterface, PaymentGatewayInterface
{
    use LogsPaymentRequests;
    use MakesHttpRequests;

    private const string GATEWAY_NAME = 'upayments';

    private readonly string $baseUrl;

    private readonly string $apiKey;

    private readonly bool $testMode;

    public function __construct(
        private readonly PaymentLogRepositoryInterface $paymentLogRepository,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
        $this->loadConfiguration();
    }

    /**
     * Create payment link
     *
     * @throws \Throwable
     */
    public function createPaymentLink(CreatePaymentLinkDTO $dto): PaymentResponseDTO
    {
        return safeProcess()
            ->onFailed(fn ($e) => PaymentResponseDTO::error(message: $e->getMessage()))
            ->do(function () use ($dto) {
                $payload = $this->buildChargePayload($dto);
                $url = $this->buildUrl($this->baseUrl, '/api/v1/charge');

                $response = $this->makeHttpRequest(
                    method: 'post',
                    url: $url,
                    data: $payload,
                    headers: ['Authorization' => 'Bearer ' . $this->apiKey],
                    paymentId: $dto->paymentId
                );

                return $this->handleChargeResponse($response, $dto);
            });
    }

    /**
     * Create customer unique token
     *
     * @throws \Throwable
     */
    public function createCustomerToken(CreateCustomerTokenDTO $dto): PaymentResponseDTO
    {
        return safeProcess()
            ->onFailed(fn ($e) => PaymentResponseDTO::error(message: $e->getMessage()))
            ->do(function () use ($dto) {
                $payload = ['customerUniqueToken' => $dto->customerUniqueToken];
                $url = $this->buildUrl($this->baseUrl, '/api/v1/create-customer-unique-token');

                $response = $this->makeHttpRequest(
                    method: 'post',
                    url: $url,
                    data: $payload,
                    headers: ['Authorization' => 'Bearer ' . $this->apiKey]
                );

                if ($response['success'] && ($response['data']['status'] ?? false)) {
                    return PaymentResponseDTO::success(
                        rawResponse: $response['data']
                    );
                }

                return PaymentResponseDTO::error(
                    message: $response['data']['message'] ?? 'Gateway error',
                    rawResponse: $response['data']
                );
            });
    }

    /**
     * Verify payment status
     *
     * @param  int  $paymentId  Internal payment ID
     * @param  string  $gatewayReferenceId  Gateway reference ID (trackId from UPayments)
     *
     * @throws \Throwable
     */
    public function verifyPayment(int $paymentId, string $gatewayReferenceId): PaymentResponseDTO
    {
        return safeProcess()
            ->onFailed(fn ($e) => PaymentResponseDTO::error(message: $e->getMessage()))
            ->do(function () use ($paymentId, $gatewayReferenceId) {
                $url = $this->buildUrl($this->baseUrl, "/api/v1/get-payment-status/{$gatewayReferenceId}");

                $response = $this->makeHttpRequest(
                    method: 'get',
                    url: $url,
                    headers: ['Authorization' => 'Bearer ' . $this->apiKey],
                    paymentId: $paymentId
                );

                if ($response['success']) {
                    return PaymentResponseDTO::success(
                        paymentId: $paymentId,
                        rawResponse: $response['data']
                    );
                }

                return PaymentResponseDTO::error(
                    message: $response['data']['message'] ?? 'Payment verification failed',
                    rawResponse: $response['data']
                );
            });
    }

    public function getGatewayName(): string
    {
        return self::GATEWAY_NAME;
    }

    protected function getPaymentLogRepository(): PaymentLogRepositoryInterface
    {
        return $this->paymentLogRepository;
    }

    public function getConfiguration(): array
    {
        return [
            'name' => self::GATEWAY_NAME,
            'test_mode' => $this->testMode,
            'base_url' => $this->baseUrl,
            'has_api_key' => ! empty($this->apiKey),
        ];
    }

    /**
     * Load gateway configuration from config files
     */
    private function loadConfiguration(): void
    {
        $this->testMode = (bool) config('payment.upayments.test_mode', true);
        $this->baseUrl = $this->testMode
            ? config('payment.upayments.test_url', 'https://sandboxapi.upayments.com')
            : config('payment.upayments.live_url', 'https://api.upayments.com');

        $this->apiKey = config('payment.upayments.api_key');
    }

    /**
     * Build charge payment payload
     */
    private function buildChargePayload(CreatePaymentLinkDTO $dto): array
    {
        return [
            'order' => [
                'id' => $dto->orderId,
                'currency' => $dto->orderCurrency,
                'amount' => $dto->orderAmount,
            ],
            'paymentGateway' => [
                'src' => $dto->paymentGatewaySrc,
            ],
            'language' => $dto->language,
            'reference' => [
                'id' => $dto->referenceId,
            ],
            'customer' => [
                'name' => $dto->customerName,
                'email' => $dto->customerEmail,
                'mobile' => $dto->customerMobile,
            ],
            'returnUrl' => $dto->returnUrl,
            'cancelUrl' => $dto->cancelUrl,
            'notificationUrl' => $dto->notificationUrl,
        ];
    }

    /**
     * Handle charge response from gateway
     *
     * UPayments response structure:
     * {
     *   "status": ...,
     *   "message": ...,
     *   "data": {
     *     "status": true,
     *     "message": "",
     *     "data": { "link": "...", "trackId": "..." }
     *   }
     * }
     */
    private function handleChargeResponse(array $response, CreatePaymentLinkDTO $dto): PaymentResponseDTO
    {
        $responseData = $response['data'] ?? [];

        // Check if HTTP request was successful
        if (! $response['success']) {
            return PaymentResponseDTO::error(
                message: $responseData['message'] ?? trans('payments.errors.payment_gateway_request_failed'),
                errorCode: $responseData['error_code'] ?? null,
                rawResponse: $responseData
            );
        }

        // Check if gateway response status is successful
        $isSuccessful = ($responseData['status'] ?? false) === true;

        // Get actual data (nested data object)
        $data = $responseData['data'] ?? [];

        // Check if required fields exist
        $hasLink = isset($data['link']);
        $hasTrackId = isset($data['trackId']);

        // If success and has required data, return success
        if ($isSuccessful && $hasLink && $hasTrackId) {
            return PaymentResponseDTO::success(
                gatewayTransactionId: (string) $data['trackId'],
                rawResponse: $data
            );
        }

        // Otherwise return error
        return PaymentResponseDTO::error(
            message: $responseData['message'] ?? trans('payments.errors.payment_gateway_failure_status'),
            errorCode: $responseData['error_code'] ?? null,
            rawResponse: $responseData
        );
    }
}
