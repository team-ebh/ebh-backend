<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\Aggregates\OnboardingPageBannerAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasMediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class OnboardingPageBanner extends Model implements HasMedia
{
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use HasMediaTrait;
    use OnboardingPageBannerAggregate;

    public const string COLUMN_ONBOARDING_PAGE_ID = 'onboarding_page_id';

    public const string COLUMN_TITLE = 'title';

    public const string COLUMN_TITLE_AR = 'title_ar';

    public const string COLUMN_SUBTITLE = 'subtitle';

    public const string COLUMN_SUBTITLE_AR = 'subtitle_ar';

    public const string COLUMN_SORT = 'sort';

    public const string IMAGE = 'image';

    public const string MEDIA_COLLECTION_NAME = 'onboarding_page_banners';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_NAME)
            ->singleFile();
    }
}
