<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfiniteScrollPaginationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * @var bool
             */
            'has_more_pages' => $this->resource['has_more_pages'],
            'next_cursor' => $this->resource['next_cursor'],
        ];
    }
}
