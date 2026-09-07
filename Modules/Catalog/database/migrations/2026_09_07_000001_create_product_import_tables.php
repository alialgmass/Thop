<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk product import (Phase 3.3, US-SEL-09/US-SEL-10). A seller uploads a CSV;
 * a queued job records one `product_import_rows` entry per data row so the
 * seller can fetch a per-row outcome report. A bad row is `failed` and skipped;
 * rows past the plan `product_limit` are `limit_rejected`. Nothing here is
 * public — imported products land in the normal `pending_review` queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_account_id')->constrained('business_accounts')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('limit_rejected_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Uploading user — bare column, matching the `products` actor-column
            // pattern (Phase 1 retrofit); not an FK.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_account_id', 'created_at']);
        });

        Schema::create('product_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_import_batch_id')->constrained('product_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->enum('status', ['imported', 'failed', 'limit_rejected']);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['product_import_batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_import_rows');
        Schema::dropIfExists('product_import_batches');
    }
};
