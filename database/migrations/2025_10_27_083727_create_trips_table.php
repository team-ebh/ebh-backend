<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->unsignedTinyInteger('trip_type_id');
            $table->unsignedTinyInteger('vehicle_type_id');
            $table->integer('passenger_count')->default(1);

            $table->decimal('accessibility_price', 12, 3)->nullable();
            $table->decimal('waiting_price', 12, 3)->nullable();
            $table->decimal('total_price', 12, 3)->nullable();
            $table->string('currency', 4);

            $table->string('status');

            $table->foreignId('demand_trip_id')->nullable()->constrained('trips')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
