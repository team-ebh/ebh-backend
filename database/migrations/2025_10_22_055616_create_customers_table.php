<?php

declare(strict_types=1);

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Customer::getTableName(), function (Blueprint $table) {
            $table->id();
            $table->string(Customer::COLUMN_FIRST_NAME);
            $table->string(Customer::COLUMN_LAST_NAME);
            $table->string(Customer::COLUMN_EMAIL)->nullable();
            $table->string(Customer::COLUMN_PHONE_NUMBER)->unique();
            $table->string(Customer::COLUMN_OTP, 6)->nullable();
            $table->timestamp(Customer::COLUMN_OTP_EXPIRES_AT)->nullable();
            $table->integer(Customer::COLUMN_STATUS);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Customer::getTableName());
    }
};
