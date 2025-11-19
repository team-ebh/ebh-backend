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
        Schema::create('trip_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('riders')->cascadeOnDelete();
            $table->unsignedTinyInteger('status');
            $table->timestamp('sent_at');
            $table->timestamp('responded_at')->nullable();
            $table->unsignedInteger('search_radius_meters')->nullable();
            $table->unsignedTinyInteger('search_attempt')->default(1);
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('estimated_arrival_seconds')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_requests');
    }
};
