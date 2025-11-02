<?php

declare(strict_types=1);

namespace App\Enums\Trip;

enum TripLocationTypeEnum: int
{
    case ORIGIN = 1;
    case DESTINATION = 2;
}
