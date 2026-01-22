<?php

declare(strict_types=1);

use App\Enums\StaticPage\ApplicationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('application_type')
                ->default(ApplicationTypeEnum::CUSTOMER->value)
                ->after('enabled')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn('application_type');
        });
    }
};
