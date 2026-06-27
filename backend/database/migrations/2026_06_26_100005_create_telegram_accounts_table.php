<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real Telegram profiles the AI "negotiator" userbot logs into to message
     * apartment owners. One row per connected profile (Telethon session).
     */
    public function up(): void
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('phone')->nullable();
            $table->string('username')->nullable();
            $table->text('session')->nullable();           // encrypted Telethon string session
            $table->boolean('is_active')->default(true);
            $table->boolean('is_simulation')->default(false);
            $table->unsignedInteger('daily_limit')->default(40);
            $table->unsignedInteger('sent_today')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_accounts');
    }
};
