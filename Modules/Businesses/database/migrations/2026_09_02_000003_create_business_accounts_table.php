<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Businesses\Enums\VerificationStatus;

/**
 * Company profile for importer / wholesaler / retailer accounts (US-ACC-03,
 * ACC-FR-03). Exactly one profile per user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('activity');
            $table->foreignId('governorate_id')->constrained('governorates');
            $table->string('address');
            $table->string('contact_person');
            // Channel list only (phone / whatsapp / …) — a flexible bag, not
            // relational data (spec Section 10.10).
            $table->json('contact_channels')->nullable();
            $table->string('verification_status')->default(VerificationStatus::Unverified->value);
            $table->boolean('onboarded_by_admin')->default(false);
            $table->timestamps();

            $table->index('governorate_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_accounts');
    }
};
