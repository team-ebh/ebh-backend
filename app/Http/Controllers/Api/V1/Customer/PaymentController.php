<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Payment\GetPaymentLinkAction;
use App\Actions\Api\V1\Customer\Payment\ProcessPaymentAction;
use App\DTOs\Api\V1\Customer\Payment\ProcessPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Payment\ProcessPaymentCallbackRequest;
use App\Http\Requests\Api\V1\Customer\Payment\ProcessPaymentWebhookRequest;
use App\Http\Resources\Api\V1\Customer\Payment\PaymentLinkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * @tags Payment
 */
class PaymentController extends Controller
{
    /**
     * Get payment link
     *
     * Generates payment link for the customer's active trip with KNET payment.
     * Available only for completed trips that haven't been paid yet.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function getLink(GetPaymentLinkAction $action): PaymentLinkResource
    {
        return new PaymentLinkResource($action());
    }

    /**
     * Process payment callback
     *
     * Processes payment gateway callback and returns deeplink for app redirection.
     * Validates pending payment, checks status with gateway, and generates deeplink.
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function processCallback(
        ProcessPaymentCallbackRequest $request,
        ProcessPaymentDTO $dto,
        ProcessPaymentAction $action
    ): RedirectResponse {
        $dto->getDataFromRequest($request);

        $result = $action($dto);

        return response()->redirectTo($result->deeplink);
    }

    /**
     * Process payment webhook
     *
     * Processes payment gateway webhook notification.
     * Validates pending payment, checks status with gateway, and updates payment status.
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function processWebhook(
        ProcessPaymentWebhookRequest $request,
        ProcessPaymentDTO $dto,
        ProcessPaymentAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        /**
         * IMPORTANT: Payment processing is intentionally disabled in webhook
         *
         * Reasons:
         * 1. The callback endpoint already handles payment verification and status updates
         * 2. Webhook is server-to-server communication - cannot redirect user to deep link
         * 3. The ProcessPaymentAction contains `canBeProcessed()` validation that prevents
         *    duplicate processing, but customers may trigger this route multiple times
         * 4. Gateway may send multiple webhook notifications for the same payment
         * 5. All payment verification and status updates are completed via callback,
         *    so webhook doesn't need to perform any additional functionality
         *
         * The webhook endpoint exists only to acknowledge receipt to the payment gateway
         * and return a success response. The actual payment processing happens in the
         * callback flow where we can properly redirect the user back to the mobile app.
         */
        // $action($dto);

        return $this->successResponse();
    }
}
