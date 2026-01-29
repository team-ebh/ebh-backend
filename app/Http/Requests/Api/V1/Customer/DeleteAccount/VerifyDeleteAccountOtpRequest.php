<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\DeleteAccount;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDeleteAccountOtpRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:4'],
        ];
    }
}
