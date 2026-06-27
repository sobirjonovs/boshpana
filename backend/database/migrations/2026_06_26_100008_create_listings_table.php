<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_owner_id')->nullable()->constrained()->nullOnDelete();

            // Provenance
            $table->string('external_id')->nullable();   // id on the source platform
            $table->string('url')->nullable();
            $table->string('source_ref')->nullable();     // e.g. telegram channel @handle / message link

            // Raw content
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();

            // Money (always normalised to USD for matching)
            $table->unsignedInteger('price')->nullable();
            $table->string('currency', 8)->default('USD');

            // Location
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();
            $table->boolean('near_metro')->nullable();
            $table->string('metro_station')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Physical attributes
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedSmallInteger('area')->nullable(); // m²
            $table->unsignedTinyInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->string('condition')->nullable();          // App\Enums\Condition
            $table->boolean('has_furniture')->nullable();
            $table->boolean('has_commission')->nullable();     // realtor fee involved?
            $table->json('amenities')->nullable();             // conditioner, washing_machine, ...

            // Tenant preferences extracted from the listing
            $table->string('gender_pref')->nullable();         // App\Enums\Gender
            $table->string('marital_pref')->nullable();        // App\Enums\MaritalStatus
            $table->string('mode')->nullable();                // App\Enums\SearchMode (solo / partnership)
            $table->unsignedTinyInteger('partners_needed')->nullable();

            // Contact + lifecycle
            $table->json('contact')->nullable();               // {phone, telegram}
            $table->timestamp('posted_at')->nullable();
            $table->string('status')->default('active');       // App\Enums\ListingStatus

            // AI analysis layer
            $table->boolean('ai_analyzed')->default(false);
            $table->text('ai_summary')->nullable();
            $table->json('ai_attributes')->nullable();
            $table->float('ai_confidence')->nullable();
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();

            $table->unique(['source_id', 'external_id']);
            $table->index(['region_id', 'district_id']);
            $table->index('price');
            $table->index('rooms');
            $table->index('posted_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
