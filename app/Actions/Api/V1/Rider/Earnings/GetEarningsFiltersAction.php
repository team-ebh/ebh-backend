<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\Earnings;

use App\Enums\Rider\EarningsFilterEnum;

readonly class GetEarningsFiltersAction
{
    public function __invoke(): array
    {
        return EarningsFilterEnum::getFiltersArray();
    }
}
