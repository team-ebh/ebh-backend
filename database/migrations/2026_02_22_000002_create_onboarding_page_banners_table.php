<?php

declare(strict_types=1);

use App\Models\OnboardingPageBanner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_page_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId(OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID)
                ->constrained('onboarding_pages')
                ->cascadeOnDelete();
            $table->string(OnboardingPageBanner::COLUMN_TITLE);
            $table->string(OnboardingPageBanner::COLUMN_TITLE_AR);
            $table->string(OnboardingPageBanner::COLUMN_SUBTITLE);
            $table->string(OnboardingPageBanner::COLUMN_SUBTITLE_AR);
            $table->unsignedTinyInteger(OnboardingPageBanner::COLUMN_SORT)->default(0);
            $table->timestamps();

            $table->unique([
                OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID,
                OnboardingPageBanner::COLUMN_SORT,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_page_banners');
    }
};
