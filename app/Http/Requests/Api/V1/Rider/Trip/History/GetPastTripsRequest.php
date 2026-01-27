<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Rider\Trip\History;

use App\Enums\Trip\TripHistoryFilterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPastTripsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /**
             * Filter by trip status
             *
             * @example "all"
             *
             * @var string
             */
            'filter' => ['sometimes', 'string', Rule::enum(TripHistoryFilterEnum::class)],
        ];
    }
}
