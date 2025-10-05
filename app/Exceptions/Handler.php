<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });
    }

    /**
     * Report or log an exception.
     *
     * @throws Throwable
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): JsonResponse | Response
    {
        if ($e instanceof ValidationException && str_starts_with($request->getHost(), 'api.')) {
            $allErrorMessages = collect($e->errors())->flatten();
            $firstMessage = $allErrorMessages->first() ?? trans('validation.validation_error');

            $remainingCount = $allErrorMessages->count() - 1;

            $finalMessage = $remainingCount > 0
                ? "{$firstMessage} " . trans('validation.and_more_errors', ['count' => $remainingCount])
                : $firstMessage;

            $transformedErrors = [];
            foreach ($e->errors() as $field => $messages) {
                $transformedErrors[] = [
                    'field' => $field,
                    'messages' => $messages,
                ];
            }

            return response()->json([
                'data' => null,
                'meta' => [
                    'message' => $finalMessage,
                    'errors' => $transformedErrors,
                ],
            ], 422);
        }

        return parent::render($request, $e);
    }
}
