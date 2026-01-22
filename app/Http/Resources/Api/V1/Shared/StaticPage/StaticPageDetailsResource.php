<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Shared\StaticPage;

use App\Enums\LanguageEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Nizek\StaticPage\Database\Models\StaticPage;

class StaticPageDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        $currentYear = now()->year;
        $isArabic = app()->getLocale() === LanguageEnum::ARABIC->value;
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
            'title' => $isArabic
                ? $staticPage->{StaticPage::COLUMN_TITLE_AR}
                : $staticPage->{StaticPage::COLUMN_TITLE},

            /**
             * @var string
             *
             * @example <div>Test Content</div>
             */
            'content' => $isArabic
                ? $staticPage->{StaticPage::COLUMN_CONTENT_AR}
                : $staticPage->{StaticPage::COLUMN_CONTENT},

            /**
             * @var string
             *
             * @example "Copyright © 2025 All Rights Reserved by EBH"
             */
            'footer' => "Copyright © $currentYear All Rights Reserved by " . config('app.name'),
        ];
    }
}
