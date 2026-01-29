<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\DeleteAccount;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:32'],
        ];
    }
}
