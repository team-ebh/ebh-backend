<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Payment\CheckPaymentStatusAction;
use App\Actions\Api\V1\Customer\Payment\DownloadReceiptAction;
use App\Actions\Api\V1\Customer\Payment\GetPaymentLinkAction;
use App\Actions\Api\V1\Customer\Payment\GetReceiptLinkAction;
use App\Actions\Api\V1\Customer\Payment\ProcessPaymentAction;
use App\Actions\Api\V1\Customer\Trip\CheckPendingPaymentAction;
use App\DTOs\Api\V1\Customer\Payment\CheckPaymentStatusDTO;
use App\DTOs\Api\V1\Customer\Payment\GetReceiptLinkDTO;
use App\DTOs\Api\V1\Customer\Payment\ProcessPaymentDTO;
use App\DTOs\Api\V1\Customer\Trip\CheckPendingPaymentDTO;
use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Payment\ProcessPaymentCallbackRequest;
use App\Http\Requests\Api\V1\Customer\Payment\ProcessPaymentWebhookRequest;
use App\Http\Resources\Api\V1\Customer\Payment\CheckPaymentStatusResource;
use App\Http\Resources\Api\V1\Customer\Payment\PaymentLinkResource;
use App\Http\Resources\Api\V1\Customer\Payment\PaymentReceiptLinkResource;
use App\Http\Resources\Api\V1\Customer\Trip\PendingPaymentResource;
use App\Models\Payment;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Payment
 */
class PaymentController extends Controller
{
    /**
     * Check pending payment
     *
     * Checks if customer has any pending payment for completed trips.
     * Returns true if there is a completed trip without payment, false otherwise.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function checkPendingPayment(
        Request $request,
        CheckPendingPaymentDTO $dto,
        CheckPendingPaymentAction $action,
    ): PendingPaymentResource {
        $dto->getDataFromRequest($request);

        return new PendingPaymentResource($action($dto));
    }

    /**
     * Get payment link
     *
     * Generates and retry payment link for the customer's active trip with KNET payment.
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
     * Check payment status
     *
     * Retrieves payment status and details for a specific payment number.
     * Returns trip information, payment method, status, and amount.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function checkPaymentStatus(
        Payment $payment,
        Request $request,
        CheckPaymentStatusDTO $dto,
        CheckPaymentStatusAction $action
    ): CheckPaymentStatusResource {
        $dto->getDataFromRequest($request);

        return new CheckPaymentStatusResource($action($dto));
    }

    /**
     * Get receipt link
     *
     * Generates a temporary signed URL to download the payment receipt.
     * The link is valid for 30 minutes and requires a valid signature.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function getReceiptLink(
        Payment $payment,
        Request $request,
        GetReceiptLinkDTO $dto,
        GetReceiptLinkAction $action
    ): PaymentReceiptLinkResource {
        $dto->getDataFromRequest($request, $payment);

        return new PaymentReceiptLinkResource($action($dto));
    }

    /**
     * Download receipt
     *
     * Downloads the payment receipt as a PDF file.
     * Requires a valid signed URL from getReceiptLink endpoint.
     * Only paid payments can have receipts.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    #[ExcludeRouteFromDocs]
    public function downloadReceipt(
        Payment $payment,
        Request $request,
        DownloadReceiptAction $action
    ): Response {
        throw_unless($request->hasValidSignature(), InvalidRequestException::class);

        return $action($payment);
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
    #[ExcludeRouteFromDocs]
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
    #[ExcludeRouteFromDocs]
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
