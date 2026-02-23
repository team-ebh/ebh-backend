<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rider;

use App\Actions\Api\V1\Rider\Onboarding\GetOnboardingPageAction;
use App\Http\Resources\Api\V1\Rider\Onboarding\OnboardingPageResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @tags Onboarding
 */
class OnboardingController
{
    /**
     * Get rider onboarding page
     *
     * Returns the active onboarding page with 3 banners for the rider app.
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
