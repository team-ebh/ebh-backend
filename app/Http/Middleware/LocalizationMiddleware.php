<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\LanguageEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        match ($request->header('language')) {
            LanguageEnum::ARABIC->value => app()->setLocale(LanguageEnum::ARABIC->value),
            default => app()->setLocale(LanguageEnum::ENGLISH->value),
        };

        return $next($request);
    }
}
