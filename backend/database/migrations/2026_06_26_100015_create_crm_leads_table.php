<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales pipeline for the future B2B offering — apartment owners and
     * realtor agencies we can sell the CRM / lead-gen product to.
     */
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->string('email')->nullable();
            $table->string('type')->default('owner');   // App\Enums\LeadType
            $table->string('status')->default('new');     // App\Enums\LeadStatus
            $table->string('source')->nullable();
            $table->decimal('potential_value', 12, 2)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('listing_owner_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
