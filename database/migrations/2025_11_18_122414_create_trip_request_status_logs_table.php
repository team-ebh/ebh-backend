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
        Schema::create('trip_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->cascadeOnDelete();
            $table->integer('status')->nullable();
            $table->nullableMorphs('changed_by'); // Can be Admin, Rider, or Customer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_request_status_logs');
    }
};
