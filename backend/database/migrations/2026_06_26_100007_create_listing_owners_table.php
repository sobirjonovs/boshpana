<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('telegram_username')->nullable();
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_realtor')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('telegram_username');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_owners');
    }
};
