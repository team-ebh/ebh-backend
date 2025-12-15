<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all riders with accessibility certifications
        $riders = DB::table('riders')
            ->whereNotNull('accessibility_certifications')
            ->get();

        foreach ($riders as $rider) {
            $certifications = json_decode($rider->accessibility_certifications, true);

            if (is_array($certifications) && ! empty($certifications)) {
                $data = [];
                $now = now();

                foreach ($certifications as $certification) {
                    $data[] = [
                        'rider_id' => $rider->id,
                        'certification_type' => $certification,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Bulk insert certifications
                if (! empty($data)) {
                    DB::table('rider_accessibility_certifications')->insert($data);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore JSON data from the new table back to riders table
        $certifications = DB::table('rider_accessibility_certifications')
            ->select('rider_id', 'certification_type')
            ->get()
            ->groupBy('rider_id');

        foreach ($certifications as $riderId => $items) {
            $certificationTypes = $items->pluck('certification_type')->toArray();

            DB::table('riders')
                ->where('id', $riderId)
                ->update([
                    'accessibility_certifications' => json_encode($certificationTypes),
                ]);
        }
    }
};
