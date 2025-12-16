<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories\Payment;

use App\Models\PaymentLog;
use App\Services\Payment\DTOs\PaymentLogDTO;

interface PaymentLogRepositoryInterface
{
    /**
     * Create a new payment log
     */
    public function create(PaymentLogDTO $dto): PaymentLog;
}
