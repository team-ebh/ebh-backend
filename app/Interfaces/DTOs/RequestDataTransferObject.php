<?php

declare(strict_types=1);

namespace App\Interfaces\DTOs;

use Illuminate\Http\Request;

interface RequestDataTransferObject extends DataTransferObject
{
    public function getDataFromRequest(Request $request): void;
}
