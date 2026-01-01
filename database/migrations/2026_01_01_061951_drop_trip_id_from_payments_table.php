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
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['trip_id']);
            // Then drop the column
            $table->dropColumn('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Re-add trip_id column
            $table->foreignId('trip_id')->nullable()->after('customer_id')->constrained('trips')->cascadeOnDelete();
        });
    }
};
