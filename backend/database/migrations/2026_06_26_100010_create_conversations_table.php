<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('telegram_account_id')->nullable()->constrained()->nullOnDelete();

            $table->string('channel')->default('telegram');
            $table->string('status')->default('pending');   // App\Enums\ConversationStatus
            $table->boolean('is_simulation')->default(false);
            $table->string('outcome')->nullable();           // agreed / declined / no_response
            $table->text('summary')->nullable();             // AI summary of the chat
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['search_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
