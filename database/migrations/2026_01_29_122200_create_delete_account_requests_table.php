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
        Schema::create('delete_account_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('requestable'); // requestable_type, requestable_id
            $table->string('security_token', 64)->nullable();
            $table->timestamp('security_token_expires_at')->nullable();
            $table->timestamp('account_deleted_at')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id', 'security_token'], 'delete_requests_token_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delete_account_requests');
    }
};
