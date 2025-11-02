<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Enums\Currency\CurrencyEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Price Resource
 *
 * Formats price data with currency for trip API responses
 */
class PriceResource extends JsonResource
{
    public function __construct(
        private readonly float $price,
        private readonly CurrencyEnum $currency,
    ) {
        parent::__construct([]);
    }

    public function toArray(Request $request): array
    {
        return [
            /**
             * Price amount
             *
             * @example 2.331
             *
             * @var float
             */
            'price' => $this->price,

            /**
             * Currency code
             *
             * @example "KWD"
             *
             * @var string
             */
            'currency' => $this->currency->getLabel(),
        ];
    }
}
