<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Interfaces\DTOs\ArrayDataTransferObject;

interface SmsInterface
{
    public function send(ArrayDataTransferObject $dto): array;

    public function validate(ArrayDataTransferObject $dto): array;
}
