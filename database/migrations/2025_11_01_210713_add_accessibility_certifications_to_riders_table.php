<?php

declare(strict_types=1);

use App\Models\Rider;
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
        Schema::table('riders', function (Blueprint $table) {
            $table->json(Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS)
                ->nullable()
                ->after(Rider::COLUMN_OTP_EXPIRES_AT);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn(Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS);
        });
    }
};
