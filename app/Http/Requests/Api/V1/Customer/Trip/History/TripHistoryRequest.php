<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Customer\Trip\History;

use App\Enums\Trip\TripHistoryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TripHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Trip history type
             *
             * @example "upcoming"
             */
            'type' => ['required', 'string', Rule::enum(TripHistoryTypeEnum::class)],
        ];
    }
}
