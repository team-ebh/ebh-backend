<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Auth\SignInAction;
use App\Actions\Api\V1\Rider\Auth\SignOutAction;
use App\Actions\Api\V1\Rider\Auth\VerifyOtpAction;
use App\DTOs\Api\V1\Rider\Auth\SignInDTO;
use App\DTOs\Api\V1\Rider\Auth\SignInVerifyOtpDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\Auth\SignInRequest;
use App\Http\Requests\Api\V1\Rider\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\Customer\Auth\SignInResource;
use App\Http\Resources\Api\V1\Rider\Auth\VerifyOtpResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Sign in rider (send OTP for verification)
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
     * Sign in Verify OTP
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
     * Sign out rider
     *
     * @authenticated
     */
    public function signOut(SignOutAction $action): JsonResponse
    {
        $action();

        return $this->successResponse();
    }
}
