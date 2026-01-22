<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Shared\StaticPage;

use App\Enums\LanguageEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Nizek\StaticPage\Database\Models\StaticPage;

class StaticPageResource extends JsonResource
{
    public function toArray($request): array
    {
        /**
         * @var StaticPage $staticPage
         */
        $staticPage = $this->resource;

        return [
            /**
             * @var int
             *
             * @example 2
             */
            'id' => $staticPage->{StaticPage::COLUMN_ID},

            /**
             * @var string
             *
             * @example About Us
             */
            'title' => app()->getLocale() === LanguageEnum::ARABIC->value
                ? $staticPage->{StaticPage::COLUMN_TITLE_AR}
                : $staticPage->{StaticPage::COLUMN_TITLE},

            /**
             * @var string
             *
             * @example https://icon.com/about-us
             */
            'icon' => $staticPage->getLastImageLink(),
        ];
    }
}
