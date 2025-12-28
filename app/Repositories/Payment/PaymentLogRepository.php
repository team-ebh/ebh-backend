<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Interfaces\Repositories\Payment\PaymentLogRepositoryInterface;
use App\Models\PaymentLog;
use App\Services\Payment\DTOs\PaymentLogDTO;

class PaymentLogRepository implements PaymentLogRepositoryInterface
{
    /**
     * Create a new payment log
     */
    public function create(PaymentLogDTO $dto): PaymentLog
    {
        return PaymentLog::query()->create([
            PaymentLog::COLUMN_PAYMENT_ID => $dto->paymentId,
            PaymentLog::COLUMN_TYPE => $dto->type,
            PaymentLog::COLUMN_METHOD => $dto->method,
            PaymentLog::COLUMN_URL => $dto->url,
            PaymentLog::COLUMN_REQUEST_HEADERS => $dto->requestHeaders,
            PaymentLog::COLUMN_REQUEST_BODY => $dto->requestBody,
            PaymentLog::COLUMN_RESPONSE_HEADERS => $dto->responseHeaders,
            PaymentLog::COLUMN_RESPONSE_BODY => $dto->responseBody,
            PaymentLog::COLUMN_STATUS_CODE => $dto->statusCode,
            PaymentLog::COLUMN_RESPONSE_TIME => $dto->responseTime,
            PaymentLog::COLUMN_ERROR => $dto->error,
        ]);
    }
}
