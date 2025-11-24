<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_location_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_location_id')->constrained('trip_locations')->cascadeOnDelete();
            $table->unsignedTinyInteger('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_location_status_logs');
    }
};
