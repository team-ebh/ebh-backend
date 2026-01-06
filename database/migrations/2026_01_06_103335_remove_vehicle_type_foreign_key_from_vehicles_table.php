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
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['vehicle_type_id']);

            // Change the column to unsignedTinyInteger to match enum values
            $table->unsignedTinyInteger('vehicle_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Change back to foreignId
            $table->foreignId('vehicle_type_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('vehicle_type_id')
                ->references('id')
                ->on('vehicle_settings')
                ->nullOnDelete();
        });
    }
};
