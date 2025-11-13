<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_accessibility_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedInteger('accessibility_requirement_id'); // Enum value
            $table->timestamps();

            $table->unique(['vehicle_id', 'accessibility_requirement_id'], 'vehicle_accessibility_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_accessibility_features');
    }
};
