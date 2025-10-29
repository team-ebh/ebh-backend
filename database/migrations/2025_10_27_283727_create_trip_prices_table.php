<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();

            $table->foreignId('from')->constrained('trip_locations')->cascadeOnDelete();
            $table->foreignId('to')->constrained('trip_locations')->cascadeOnDelete();

            $table->decimal('base_fare_price', 12, 3);
            $table->string('currency', 4);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_prices');
    }
};
