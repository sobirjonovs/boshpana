<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();

            $table->float('score')->default(0);          // 0..100 match quality
            $table->json('score_breakdown')->nullable();  // per-criterion contribution
            $table->string('status')->default('candidate'); // App\Enums\MatchStatus
            $table->text('reason')->nullable();           // why it matched / owner's answer
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['search_request_id', 'listing_id']);
            $table->index(['search_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_matches');
    }
};
