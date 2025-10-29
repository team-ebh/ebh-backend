<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->cascadeOnDelete();

            $table->string('location_title')->nullable();
            $table->string('location_sub_title')->nullable();
            $table->float('latitude');
            $table->float('longitude');
            $table->unsignedTinyInteger('type'); // origin or destination
            $table->integer('sequence');
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_locations');
    }
};
