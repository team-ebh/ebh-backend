<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Default phone code prefix
             *
             * @example "+965"
             *
             * @var string
             */
            'code' => defaultPrefixPhoneNumber(),

            /**
             * Customer phone number
             *
             * @example "88997788"
             *
             * @var string
             */
            'number' => $this->resource,
        ];
    }
}
