<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();

            // Location criteria
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();

            // Money (USD)
            $table->unsignedInteger('price_min')->nullable();
            $table->unsignedInteger('price_max')->nullable();
            $table->string('currency', 8)->default('USD');

            // Apartment criteria
            $table->json('rooms')->nullable();              // e.g. [1,2,3]
            $table->string('condition')->nullable();        // App\Enums\Condition
            $table->string('has_furniture')->nullable();    // App\Enums\TriState
            $table->string('has_commission')->nullable();   // App\Enums\TriState
            $table->unsignedSmallInteger('area_min')->nullable();
            $table->unsignedSmallInteger('area_max')->nullable();

            // Living arrangement
            $table->string('mode')->default('solo');        // App\Enums\SearchMode
            $table->unsignedTinyInteger('partners_count')->nullable();
            $table->string('near_metro')->nullable();       // App\Enums\TriState

            // Tenant profile
            $table->string('gender')->nullable();           // App\Enums\Gender
            $table->string('marital_status')->nullable();   // App\Enums\MaritalStatus
            $table->text('free_text')->nullable();          // optional natural-language goal

            // Lifecycle
            $table->string('status')->default('draft');     // App\Enums\SearchStatus
            $table->boolean('is_simulation')->default(false);
            $table->unsignedTinyInteger('current_step')->default(0);

            // Progress telemetry (drives the bot's animated progress message)
            $table->unsignedTinyInteger('progress')->default(0); // 0..100
            $table->unsignedInteger('scanned_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('contacted_count')->default(0);
            $table->unsignedInteger('agreed_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_progress_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_requests');
    }
};
