<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\Location;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Rider;
use Illuminate\Http\Request;

class UpdateLocationDTO implements RequestDataTransferObject
{
    public Rider $rider;

    public float $latitude;

    public float $longitude;

    public function getDataFromRequest(Request $request): void
    {
        $this->rider = auth()->guard('rider')->user();
        $this->latitude = (float) $request->input('latitude');
        $this->longitude = (float) $request->input('longitude');
    }
}
