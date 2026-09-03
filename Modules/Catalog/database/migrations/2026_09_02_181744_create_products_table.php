<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_account_id')->constrained('business_accounts')->cascadeOnDelete();
            $table->foreignId('fabric_type_id')->constrained('fabric_types');
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('governorate_id')->constrained('governorates');

            // Display name. name_en is nullable (Implementation Assumption: the
            // spec's attribute list has no explicit name; §10.3 FULLTEXT plan
            // implies a display name is needed for search and the product card,
            // with at least one of the bilingual names required to publish).
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('weight_gsm')->nullable();

            // BR-SEL-03: exactly one of {price, price_on_contact}.
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('price_on_contact')->default(false);
            // schema-forward: only EGP is used in R1; the column is kept per §10.3.
            $table->string('currency', 3)->default('EGP');

            $table->enum('unit', ['per_meter', 'per_kg'])->default('per_meter');
            $table->unsignedInteger('moq')->nullable();
            $table->unsignedInteger('quantity_available')->default(0);

            // Lifecycle: draft → pending_review → published / hidden / unavailable / rejected.
            $table->enum('status', [
                'draft', 'pending_review', 'published', 'hidden', 'unavailable', 'rejected',
            ])->default('draft');
            $table->text('rejection_reason')->nullable();

            // Actor columns (Phase 1 retrofit pattern).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Phase 4 filter indexes (§10.3), created up front so Search consumes them.
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['status']);
            $table->index(['business_account_id', 'status']);
            $table->index(['fabric_type_id', 'governorate_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
