<?php

declare(strict_types=1);

namespace App\Pipelines\Api\V1\Customer\Trip\ConfirmTrip;

use App\Enums\Trip\TripStatusEnum;
use App\Interfaces\Repositories\Api\V1\Customer\Trip\TripRepositoryInterface;
use Closure;

/**
 * Update Payment And Status Pipe
 *
 * Updates trip payment method and status to PENDING_RIDER
 */
readonly class UpdatePaymentAndStatusPipe
{
    public function __construct(
        private TripRepositoryInterface $tripRepository
    ) {}

    public function handle(ConfirmTripContext $context, Closure $next): mixed
    {
        // Update payment method and trip status to PENDING_RIDER
        $this->tripRepository->updatePaymentMethodAndStatus(
            $context->dto->trip,
            $context->dto->paymentMethod,
            TripStatusEnum::PENDING_RIDER
        );

        return $next($context);
    }
}
