<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_custom_rate')->default(false)->after('commission_rate');
        });

        // Update existing companies: if they have a commission_rate > 0, mark as custom
        // Otherwise set to default rate
        $defaultRate = Setting::getDefaultCommissionRate();

        DB::table('companies')
            ->whereNotNull('commission_rate')
            ->where('commission_rate', '>', 0)
            ->update(['is_custom_rate' => true]);

        DB::table('companies')
            ->where(function ($query) {
                $query->whereNull('commission_rate')
                    ->orWhere('commission_rate', 0);
            })
            ->update([
                'commission_rate' => $defaultRate,
                'is_custom_rate' => false,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_custom_rate');
        });
    }
};
