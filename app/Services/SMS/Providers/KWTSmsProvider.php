<?php

declare(strict_types=1);

namespace App\Services\SMS\Providers;

use App\Interfaces\DTOs\ArrayDataTransferObject;
use App\Services\SMS\SmsInterface;
use App\Services\SMS\SmsLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KWTSmsProvider implements SmsInterface
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     * @throws \Throwable
     */
    public function send(ArrayDataTransferObject $dto): array
    {
        $data = [
            'username' => config('sms.kwt_sms.username'),
            'password' => config('sms.kwt_sms.password'),
            'sender' => 'KWT-SMS',
            'mobile' => $dto->phoneNumber,
            // lang:
            // 1: English
            // 2: Arabic (CP1256)
            // 3: Arabic (UTF-8)
            // 4: Unicode
            'lang' => 3,
            'test' => 0,
            'message' => $dto->message,
        ];

        return safeProcess()
            ->onFailed(function ($e) use ($dto, $data) {
                // Log error to SMS logs table
                SmsLogger::logError($e, $dto, $data);

                Log::critical('sms_provider_error', [
                    'error' => $e->getMessage(),
                    'receiver_type' => get_class($dto->receiver),
                    'receiver_id' => $dto->receiver->id,
                ]);

                throw $e;
            })
            ->do(function () use ($data, $dto) {
                $response = Http::retry(3)
                    ->withHeaders([
                        'Accept' => 'application/json',
                    ])
                    ->timeout(3)
                    ->post(config('sms.kwt_sms.url'), $data);

                SmsLogger::log($response, $dto, $data);

                return [
                    'success' => $response->successful(),
                    'result' => $response->body(),
                ];
            });
    }

    public function validate(ArrayDataTransferObject $dto): array
    {
        return [];
    }
}
