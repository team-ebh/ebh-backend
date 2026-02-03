<?php

declare(strict_types=1);

namespace App\Services\SMS\DTOs;

use App\Enums\SMS\SmsProvidersEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Interfaces\DTOs\ArrayDataTransferObject;
use Illuminate\Database\Eloquent\Model;

class SignUpSmsDTO implements ArrayDataTransferObject
{
    public Model $receiver;

    public string $phoneNumber;

    public ?string $message;

    public SmsProvidersEnum $smsProvider;

    public SmsTypesEnum $smsType;

    public function getDataFromArray(array $data): void
    {
        $this->receiver = $data['receiver'];
        $this->phoneNumber = '965' . $this->receiver->phone_number;
        $this->message = $data['message'];
        $this->smsProvider = SmsProvidersEnum::tryFrom(config('sms.active_provider')) ?? SmsProvidersEnum::KWT_SMS;
        $this->smsType = SmsTypesEnum::SIGNUP;
    }
}
