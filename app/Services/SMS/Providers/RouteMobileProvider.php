<?php

declare(strict_types=1);

namespace App\Services\SMS\Providers;

use App\Interfaces\DTOs\ArrayDataTransferObject;
use App\Services\SMS\SmsInterface;

class RouteMobileProvider implements SmsInterface
{
    public function send(ArrayDataTransferObject $dto): array
    {
        return [];
    }

    public function validate(ArrayDataTransferObject $dto): array
    {
        return [];
    }
}
