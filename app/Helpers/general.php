<?php

declare(strict_types=1);

use App\Enums\ApplicationEnvironmentEnum;
use App\Models\Payment;
use App\Models\Trip;
use App\Services\SafeProcess;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

if (! function_exists('adminPanelDataFormat')) {
    function adminPanelDataFormat(): string
    {
        return 'M d, Y';
    }
}

if (! function_exists('adminPanelTimeFormat')) {
    function adminPanelTimeFormat(): string
    {
        return 'H:i';
    }
}

if (! function_exists('getDefaultImageUrl')) {
    function getDefaultImageUrl(): ?string
    {
        if (ApplicationEnvironmentEnum::isDevelopmentEnvironment()) {
            return asset('images/default-avatar.png');
        }

        return null;
    }
}

if (! function_exists('getDefaultAvatar')) {
    function getDefaultAvatar(): ?string
    {
        return asset('images/default-avatar.png');
    }
}

if (! function_exists('getDefaultSVGUrl')) {
    function getDefaultSVGUrl(): ?string
    {
        if (ApplicationEnvironmentEnum::isDevelopmentEnvironment()) {
            return asset('images/logo/light.svg');
        }

        return null;
    }
}

if (! function_exists('defaultPrefixPhoneNumber')) {
    function defaultPrefixPhoneNumber(): string
    {
        return '+965';
    }
}

if (! function_exists('translated')) {
    function translated(Model $model, string $field): mixed
    {
        return $model->translated($field);
    }
}

if (! function_exists('safeProcess')) {

    function safeProcess(): SafeProcess
    {
        return new SafeProcess();
    }
}

if (! function_exists('generateOtpCode')) {

    /**
     * @throws \Random\RandomException
     */
    function generateOtpCode(): string
    {
        return str_pad((string) random_int(1111, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('priceFormat')) {
    function priceFormat($price): ?string
    {
        if (! $price) {
            return (string) $price;
        }

        return number_format(
            num: numberFormat($price),
            decimals: 3,
            thousands_separator: ''
        );
    }
}

if (! function_exists('numberFormat')) {
    function numberFormat(int | float | null | string $number, ?int $decimal = null): float | int | null | string
    {
        $decimalPlaces = 3;

        if (! is_null($decimal)) {
            $decimalPlaces = $decimal;
        }

        if (is_null($number)) {
            return null;
        }

        if (! is_numeric($number)) {
            return $number;
        }

        if (numericVal($number) === 0) {
            return 0;
        }

        if (numericVal($number) === 0.0000) {
            return 0;
        }

        if (abs(floatval($number)) <= 0.01) {
            if ($number < 0) {
                return -0.01;
            }

            return 0.01;
        }

        $result = bcdiv((string) $number, '1', $decimalPlaces);

        /**
         * if result actually is integer, cast it to integer to be sure that zero decimal values will be removed
         */
        if ($result - (int) $result === 0) {
            return (int) $result;
        }

        return (float) $result;
    }
}

if (! function_exists('numericVal')) {
    function numericVal(null | string | float | int | array $numericValue): float | int | string | null | array
    {
        return is_numeric($numericValue) ? $numericValue + 0 : $numericValue;
    }
}

if (! function_exists('getAuthenticatedUser')) {
    /**
     * Get the currently authenticated user from any guard.
     * Checks in order: customer, rider, admin (web).
     */
    function getAuthenticatedUser(): mixed
    {
        return Auth::guard('customer')->user()
            ?? Auth::guard('rider')->user()
            ?? Auth::guard('web')->user();
    }
}

if (! function_exists('generatePaymentNumber')) {
    /**
     * Generate unique payment number
     *
     * @throws \Random\RandomException
     */
    function generatePaymentNumber(): string
    {
        do {
            $code = 'PAY-' . random_int(11111111, 99999999);
        } while (Payment::query()->where(Payment::COLUMN_PAYMENT_NUMBER, $code)->exists());

        return $code;
    }
}

if (! function_exists('tripNumberFormat')) {
    /**
     * Generate unique trip number
     */
    function tripNumberFormat(Trip $trip): string
    {
        return 'TRP-' . $trip->{Trip::COLUMN_ID};
    }
}

if (! function_exists('getApiUrl')) {
    /**
     * Get API domain URL
     */
    function getApiUrl(): string
    {
        $domain = config('app.domains.api');
        $port = parse_url(config('app.url'), PHP_URL_PORT);
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';

        if ($port && $port !== 80 && $port !== 443) {
            return "{$scheme}://{$domain}:{$port}";
        }

        return "{$scheme}://{$domain}";
    }
}

if (! function_exists('getInfiniteScroll')) {
    function getInfiniteScroll(CursorPaginator $paginator): array
    {
        if (! $paginator->hasMorePages()) {
            $nextCursor = null;
        } else {
            parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $queryParams);
            $nextCursor = $queryParams['cursor'] ?? null;
        }

        return [
            'data' => collect($paginator->items()),
            'pagination' => [
                'has_more_pages' => $paginator->hasMorePages(),
                'next_cursor' => is_null($nextCursor) ? null : (string) $nextCursor,
            ],
        ];
    }
}
