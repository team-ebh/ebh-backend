<?php

declare(strict_types=1);

namespace App\Interfaces\DTOs;

interface ArrayDataTransferObject extends DataTransferObject
{
    public function getDataFromArray(array $data): void;
}
