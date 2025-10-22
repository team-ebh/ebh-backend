<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Auth\SignInAction;
use App\Actions\Api\V1\Customer\Auth\SignOutAction;
use App\Actions\Api\V1\Customer\Auth\SignUpAction;
use App\Actions\Api\V1\Customer\Auth\VerifyOtpAction;
use App\DTOs\Api\V1\Customer\Auth\SignInDTO;
use App\DTOs\Api\V1\Customer\Auth\SignInVerifyOtpDTO;
use App\DTOs\Api\V1\Customer\Auth\SignUpDTO;
use App\DTOs\Api\V1\Customer\Auth\SignUpVerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Auth\SignInRequest;
use App\Http\Requests\Api\V1\Customer\Auth\SignUpRequest;
use App\Http\Requests\Api\V1\Customer\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\Customer\Auth\SignInResource;
use App\Http\Resources\Api\V1\Customer\Auth\SignUpResource;
use App\Http\Resources\Api\V1\Customer\Auth\VerifyOtpResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Customer Auth
 */
class AuthController extends Controller
{
    /**
     * Sign up a new customer (send OTP for verification)
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function signUp(
        SignUpRequest $request,
        SignUpDTO $dto,
        SignUpAction $action
    ): SignUpResource {
        $dto->getDataFromRequest($request);

        return new SignUpResource($action($dto));
    }

    /**
     * Verify OTP and complete authentication
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function signUpVerifyOtp(
        VerifyOtpRequest $request,
        SignUpVerifyOtpDTO $dto,
        VerifyOtpAction $action
    ): VerifyOtpResource {
        $dto->getDataFromRequest($request);

        return new VerifyOtpResource($action($dto));
    }

    /**
     * Sign in customer (send OTP for verification)
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function signIn(
        SignInRequest $request,
        SignInDTO $dto,
        SignInAction $action
    ): SignInResource {
        $dto->getDataFromRequest($request);

        return new SignInResource($action($dto));
    }

    /**
     * Verify OTP and complete authentication
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function signInVerifyOtp(
        VerifyOtpRequest $request,
        SignInVerifyOtpDTO $dto,
        VerifyOtpAction $action
    ): VerifyOtpResource {
        $dto->getDataFromRequest($request);

        return new VerifyOtpResource($action($dto));
    }

    /**
     * Sign out customer
     *
     * @authenticated
     */
    public function signOut(SignOutAction $action): JsonResponse
    {
        $action();

        return $this->successResponse();
    }
}
