<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Interfaces\DTOs\ArrayDataTransferObject;
use App\Models\SmsLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class SmsLogger
{
    /**
     * Log successful or failed SMS attempt with HTTP response
     */
    public static function log(Response $response, ArrayDataTransferObject $dto, array $data): void
    {
        $phoneNumber = $dto->phoneNumber;

        if (Str::startsWith($dto->phoneNumber, '965')) {
            $phoneNumber = substr($dto->phoneNumber, 3);
        }

        $logData = [
            SmsLog::COLUMN_RECEIVABLE_TYPE => get_class($dto->receiver),
            SmsLog::COLUMN_RECEIVABLE_ID => $dto->receiver->id,
            SmsLog::COLUMN_REQUEST_DATA => $data,
            SmsLog::COLUMN_PROVIDER_RESPONSE => $response->body(),
            SmsLog::COLUMN_RECIPIENT_NUMBER => $phoneNumber,
            SmsLog::COLUMN_SMS_TYPE => $dto->smsType,
            SmsLog::COLUMN_SMS_PROVIDER => $dto->smsProvider,
            SmsLog::COLUMN_MESSAGE => $data['message'] ?? null,
            SmsLog::COLUMN_SENT_AT => now(),
            SmsLog::COLUMN_IS_SUCCESSFUL => $response->successful(),
            SmsLog::COLUMN_STATUS_CODE => (string) $response->status(),
        ];

        SmsLog::query()->create($logData);
    }

    /**
     * Log SMS error when no HTTP response is available
     * (connection failures, timeouts, exceptions, etc.)
     */
    public static function logError(\Throwable $exception, ArrayDataTransferObject $dto, array $data): void
    {
        $phoneNumber = $dto->phoneNumber;

        if (Str::startsWith($dto->phoneNumber, '965')) {
            $phoneNumber = substr($dto->phoneNumber, 3);
        }

        $errorResponse = [
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        $logData = [
            SmsLog::COLUMN_RECEIVABLE_TYPE => get_class($dto->receiver),
            SmsLog::COLUMN_RECEIVABLE_ID => $dto->receiver->id,
            SmsLog::COLUMN_REQUEST_DATA => $data,
            SmsLog::COLUMN_PROVIDER_RESPONSE => json_encode($errorResponse),
            SmsLog::COLUMN_RECIPIENT_NUMBER => $phoneNumber,
            SmsLog::COLUMN_SMS_TYPE => $dto->smsType,
            SmsLog::COLUMN_SMS_PROVIDER => $dto->smsProvider,
            SmsLog::COLUMN_MESSAGE => $data['message'] ?? null,
            SmsLog::COLUMN_SENT_AT => now(),
            SmsLog::COLUMN_IS_SUCCESSFUL => false,
            SmsLog::COLUMN_STATUS_CODE => null,
        ];

        SmsLog::query()->create($logData);
    }
}
