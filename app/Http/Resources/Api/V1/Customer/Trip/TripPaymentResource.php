<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * @example Base fare
             *
             * @var string
             */
            'label' => $this->resource['label'],

            /**
             * @example Base fare
             *
             * @var string|null
             */
            'sub_label' => $this->when(isset($this->resource['sub_label']), fn () => $this->resource['sub_label']),

            /**
             * @example to be calculated or 2.300 KWD
             *
             * @var string
             */
            'value' => $this->resource['value'],
        ];
    }
}
