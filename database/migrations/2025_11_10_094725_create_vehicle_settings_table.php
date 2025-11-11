<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // car_types, car_colors, etc.
            $table->string('name')->nullable();
            $table->string('name_ar')->nullable();
            $table->integer('capacity')->nullable(); // for passenger_capacity
            $table->integer('order')->default(0); // for ordering
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_settings');
    }
};
