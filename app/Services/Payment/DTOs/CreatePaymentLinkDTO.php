<?php

declare(strict_types=1);

namespace App\Services\Payment\DTOs;

use App\Interfaces\DTOs\ArrayDataTransferObject;

/**
 * Create Payment Link DTO
 *
 * Data transfer object for creating payment link in payment gateway
 */
class CreatePaymentLinkDTO implements ArrayDataTransferObject
{
    // Order information
    public string $orderId;

    public string $orderCurrency;

    public string $orderAmount;

    // Payment Gateway
    public string $paymentGatewaySrc;

    // Language
    public string $language;

    // Reference
    public string $referenceId;

    // Payment ID
    public int $paymentId;

    // Customer information
    public string $customerName;

    public ?string $customerEmail = null;

    public string $customerMobile;

    // URLs
    public string $returnUrl;

    public string $cancelUrl;

    public string $notificationUrl;

    public function getDataFromArray(array $data): void
    {
        // Order
        $this->orderId = $data['order_id'];
        $this->orderCurrency = $data['order_currency'];
        $this->orderAmount = $data['order_amount'];

        // Payment Gateway
        $this->paymentGatewaySrc = $data['payment_gateway_src'];

        // Language
        $this->language = $data['language'];

        // Reference
        $this->referenceId = $data['reference_id'];

        // Payment ID
        $this->paymentId = $data['payment_id'];

        // Customer
        $this->customerName = $data['customer_name'];
        $this->customerEmail = $data['customer_email'];
        $this->customerMobile = $data['customer_mobile'];

        // URLs
        $this->returnUrl = $data['return_url'];
        $this->cancelUrl = $data['cancel_url'];
        $this->notificationUrl = $data['notification_url'];
    }
}
