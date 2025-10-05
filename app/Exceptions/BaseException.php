<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class BaseException extends Exception
{
    public function render(): false | JsonResponse
    {
        if (request()->acceptsJson()) {
            return response()->json([
                'data' => null,
                'meta' => [
                    'message' => $this->message(),
                    'errors' => null,
                ],
            ], $this->statusCode());
        }

        return false;
    }

    public function report(): false
    {
        return false;
    }

    abstract public function message(): string;

    abstract public function statusCode(): int;
}
