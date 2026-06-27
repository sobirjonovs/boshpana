<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language', 5)->default('uz');   // App\Enums\Language
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();           // App\Enums\Gender
            $table->string('marital_status')->nullable();   // App\Enums\MaritalStatus
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedInteger('free_searches_left')->default(3);
            $table->timestamp('premium_until')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
