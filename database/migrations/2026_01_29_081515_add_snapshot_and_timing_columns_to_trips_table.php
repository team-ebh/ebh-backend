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
        Schema::table('trips', function (Blueprint $table) {
            // Vehicle snapshot - stores vehicle info at time of trip acceptance
            $table->json('vehicle_snapshot')->nullable()->after('demand_trip_id');

            // Trip timing data
            $table->timestamp('picked_up_at')->nullable()->after('vehicle_snapshot');
            $table->timestamp('completed_at')->nullable()->after('picked_up_at');

            // Calculated trip metrics
            $table->unsignedInteger('duration_minutes')->nullable()->after('completed_at');
            $table->unsignedInteger('distance_meters')->nullable()->after('duration_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_snapshot',
                'picked_up_at',
                'completed_at',
                'duration_minutes',
                'distance_meters',
            ]);
        });
    }
};
