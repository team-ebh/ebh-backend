<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class GeocodingApiErrorException extends BaseException
{
    public function __construct(
        private readonly string $apiMessage = ''
    ) {
        parent::__construct();
    }

    public function message(): string
    {
        return trans('errors.geocoding.api_error', ['message' => $this->apiMessage]);
    }

    public function statusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
