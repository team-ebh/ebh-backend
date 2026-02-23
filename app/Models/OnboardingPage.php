<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Traits\Model\Aggregates\OnboardingPageAggregate;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingPage extends Model
{
    use HasDefaultColumnModelTrait;
    use HasFactory;
    use LogsActivity;
    use OnboardingPageAggregate;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_APPLICATION_TYPE = 'application_type';

    public const string COLUMN_ENABLED = 'enabled';

    protected $casts = [
        self::COLUMN_ENABLED => 'boolean',
        self::COLUMN_APPLICATION_TYPE => OnboardingApplicationTypeEnum::class,
    ];

    public function scopeEnabled(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(self::COLUMN_ENABLED, true);
    }

    public function makeOnlyOneEnabledOnboardingPage(): void
    {
        if ($this->{self::COLUMN_ENABLED}) {
            self::query()
                ->where(self::COLUMN_ID, '<>', $this->{self::COLUMN_ID})
                ->where(self::COLUMN_APPLICATION_TYPE, $this->{self::COLUMN_APPLICATION_TYPE})
                ->update([self::COLUMN_ENABLED => false]);
        }
    }
}
