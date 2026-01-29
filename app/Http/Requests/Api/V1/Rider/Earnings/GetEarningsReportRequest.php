<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Earnings;

use App\Enums\Rider\EarningsFilterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetEarningsReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /**
             * Filter by period: today, this_week, past_trips
             *
             * @example "today"
             *
             * @var string
             */
            'filter' => ['sometimes', 'string', Rule::enum(EarningsFilterEnum::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filter.enum' => trans('riders.api.earnings.validation.filter.enum'),
        ];
    }
}
