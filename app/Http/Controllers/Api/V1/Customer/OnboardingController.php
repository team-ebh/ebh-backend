<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Api\V1\Customer\Onboarding\GetOnboardingPageAction;
use App\Http\Resources\Api\V1\Customer\Onboarding\OnboardingPageResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @tags Onboarding
 */
class OnboardingController
{
    /**
     * Get customer onboarding page
     *
     * Returns the active onboarding page with 3 banners for the customer app.
     *
     * @unauthenticated
     *
     * @throws \Throwable
     */
    public function __invoke(GetOnboardingPageAction $action): OnboardingPageResource
    {
        $onboardingPage = $action();

        throw_if(
            ! $onboardingPage,
            NotFoundHttpException::class
        );

        return new OnboardingPageResource($onboardingPage);
    }
}
