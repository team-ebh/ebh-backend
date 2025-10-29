<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_accessibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->integer('accessibility_requirement');
            $table->timestamps();
            $table->unique(['trip_id', 'accessibility_requirement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_accessibility');
    }
};
