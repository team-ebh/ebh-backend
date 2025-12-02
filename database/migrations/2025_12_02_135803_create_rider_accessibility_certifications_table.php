<?php

declare(strict_types=1);

use App\Models\RiderAccessibilityCertification;
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
        Schema::create('rider_accessibility_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId(RiderAccessibilityCertification::COLUMN_RIDER_ID)
                ->constrained('riders')
                ->cascadeOnDelete();
            $table->string(RiderAccessibilityCertification::COLUMN_CERTIFICATION_TYPE);
            $table->timestamps();

            // Ensure unique combination of rider_id and certification_type
            $table->unique([
                RiderAccessibilityCertification::COLUMN_RIDER_ID,
                RiderAccessibilityCertification::COLUMN_CERTIFICATION_TYPE,
            ], 'rider_cert_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_accessibility_certifications');
    }
};
