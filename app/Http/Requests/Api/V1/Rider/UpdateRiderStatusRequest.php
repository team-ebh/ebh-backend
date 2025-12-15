<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider;

use App\Enums\Rider\RiderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiderStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(RiderStatusEnum::class),
                Rule::in([
                    RiderStatusEnum::ONLINE->value,
                    RiderStatusEnum::OFFLINE->value,
                ]),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => trans('riders.api.validation.status.required'),
            'status.in' => trans('riders.api.validation.status.in'),
        ];
    }
}
