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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('receivable'); // Polymorphic relation (Customer or Rider)
            $table->json('request_data')->nullable();
            $table->json('provider_response')->nullable();
            $table->string('recipient_number')->index();
            $table->unsignedSmallInteger('sms_type')->nullable();
            $table->string('sms_provider')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('sent_at');
            $table->boolean('is_successful')->nullable();
            $table->string('status_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
