<?php

declare(strict_types=1);

use App\Models\OnboardingPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_pages', function (Blueprint $table) {
            $table->id();
            $table->string(OnboardingPage::COLUMN_NAME);
            $table->string(OnboardingPage::COLUMN_APPLICATION_TYPE)->index();
            $table->boolean(OnboardingPage::COLUMN_ENABLED)->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_pages');
    }
};
