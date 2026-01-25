<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Rider\StaticPage;

use App\Enums\StaticPage\ApplicationTypeEnum;
use Illuminate\Database\Eloquent\Collection;
use Nizek\StaticPage\Database\Models\StaticPage;

class StaticPageListAction
{
    public function __invoke(): Collection
    {
        return StaticPage::query()
            ->select([
                StaticPage::COLUMN_ID,
                StaticPage::COLUMN_TITLE,
                StaticPage::COLUMN_TITLE_AR,
            ])
            ->with('image')
            ->where(StaticPage::COLUMN_ENABLED, true)
            ->where('application_type', ApplicationTypeEnum::RIDER->value)
            ->latest(StaticPage::COLUMN_ID)
            ->get();
    }
}
