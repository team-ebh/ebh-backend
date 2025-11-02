<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\CreateTrip;

use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Attach Accessibility Requirements Pipe
 *
 * Attaches accessibility requirements to the created trip
 */
class AttachAccessibilityRequirementsPipe
{
    public function __construct(
        protected TripRepositoryInterface $tripRepository,
    ) {}

    public function handle(TripCreationContext $context, Closure $next): mixed
    {
        if (! empty($context->dto->accessibilityRequirements)) {
            $this->tripRepository->attachAccessibilityRequirements(
                $context->trip,
                $context->dto->accessibilityRequirements
            );
        }

        return $next($context);
    }
}
