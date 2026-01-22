<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Shared\StaticPage;

use Illuminate\Http\Resources\Json\JsonResource;

class StaticPagesResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'static_pages' => StaticPageResource::collection($this->resource),
        ];
    }
}
