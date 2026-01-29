<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\DeleteAccount\ConfirmDeleteAccountAction;
use App\Actions\Api\V1\Rider\DeleteAccount\SendDeleteAccountOtpAction;
use App\Actions\Api\V1\Rider\DeleteAccount\VerifyDeleteAccountOtpAction;
use App\DTOs\Api\V1\Rider\DeleteAccount\ConfirmDeleteAccountDTO;
use App\DTOs\Api\V1\Rider\DeleteAccount\SendDeleteAccountOtpDTO;
use App\DTOs\Api\V1\Rider\DeleteAccount\VerifyDeleteAccountOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\DeleteAccount\ConfirmDeleteAccountRequest;
use App\Http\Requests\Api\V1\Rider\DeleteAccount\VerifyDeleteAccountOtpRequest;
use App\Http\Resources\Api\V1\Rider\DeleteAccount\SendDeleteAccountOtpResource;
use App\Http\Resources\Api\V1\Rider\DeleteAccount\VerifyDeleteAccountOtpResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Delete Account
 */
class DeleteAccountController extends Controller
{
    /**
     * Send OTP for Delete Account
     *
     * Sends an OTP to the rider's phone number to initiate account deletion.
     * If an active OTP exists, returns the existing expiration time.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function sendOtp(
        Request $request,
        SendDeleteAccountOtpDTO $dto,
        SendDeleteAccountOtpAction $action
    ): SendDeleteAccountOtpResource {
        $dto->getDataFromRequest($request);

        return new SendDeleteAccountOtpResource($action($dto));
    }

    /**
     * Verify OTP for Delete Account
     *
     * Verifies the OTP and returns a security token for confirming account deletion.
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function verifyOtp(
        VerifyDeleteAccountOtpRequest $request,
        VerifyDeleteAccountOtpDTO $dto,
        VerifyDeleteAccountOtpAction $action
    ): VerifyDeleteAccountOtpResource {
        $dto->getDataFromRequest($request);

        return new VerifyDeleteAccountOtpResource($action($dto));
    }

    /**
     * Confirm Delete Account
     *
     * Confirms account deletion using the security token.
     * This action is irreversible and will:
     * - Anonymize the rider's phone number
     * - Set account status to DELETED
     * - Revoke all authentication tokens
     *
     * @authenticated
     *
     * @throws \Throwable
     */
    public function confirmDeleteAccount(
        ConfirmDeleteAccountRequest $request,
        ConfirmDeleteAccountDTO $dto,
        ConfirmDeleteAccountAction $action
    ): JsonResponse {
        $dto->getDataFromRequest($request);

        $action($dto);

        return $this->successResponse();
    }
}
