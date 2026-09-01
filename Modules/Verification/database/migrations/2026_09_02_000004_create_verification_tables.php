<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Verification\Enums\VerificationRequestStatus;

/**
 * Verification documents and their admin review cycle (US-ACC-04, US-ADM-01,
 * DAT-FR-02). Document *type* is a taxonomy row so the mandatory-document list
 * can change without a deploy (Open Decision #5, MNT-NFR-02).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_account_id')->constrained('business_accounts')->cascadeOnDelete();
            $table->string('status')->default(VerificationRequestStatus::Pending->value);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['business_account_id', 'status']);
        });

        Schema::create('verification_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('verification_request_id')->constrained('verification_requests')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('original_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('document_types');
    }
};
