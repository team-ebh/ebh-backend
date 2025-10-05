<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\Api\SuccessResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    public function sendResponse(
        JsonResource | ResourceCollection | null $data,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $message = $message ?? trans('general.api.success_message');

        return response()->json([
            'data' => $this->getData($data),
            'meta' => [
                'message' => $message,
                'errors' => [],
            ],
        ]);
    }

    private function getData(ResourceCollection | JsonResource | array | Collection | null $data)
    {
        if ($data instanceof ResourceCollection && $data->collection->isEmpty()) {
            return null;
        }

        if ($data instanceof JsonResource && ! $data->resource) {
            return null;
        }

        return $data;
    }

    public function successResponse(): JsonResponse
    {
        return $this->sendResponse(new SuccessResource(null));
    }
}
