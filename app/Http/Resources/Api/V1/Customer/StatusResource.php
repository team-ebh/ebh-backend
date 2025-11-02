<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Customer status value
             *
             * @example "active" or 12
             *
             * @var string|int
             */
            'id' => $this->resource->value,

            /**
             * Customer status label
             *
             * @example "Active"
             *
             * @var string
             */
            'label' => $this->resource->getLabel(),
        ];
    }
}
