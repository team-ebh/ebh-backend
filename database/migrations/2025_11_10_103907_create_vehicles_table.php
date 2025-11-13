<?php

declare(strict_types=1);

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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('riders')->cascadeOnDelete();
            $table->foreignId('car_type_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->foreignId('car_color_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->foreignId('car_make_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->foreignId('car_model_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->foreignId('passenger_capacity_id')->nullable()->constrained('vehicle_settings')->nullOnDelete();
            $table->year('year')->nullable();
            $table->string('plate_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
