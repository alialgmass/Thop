<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A buyer-initiated contact with a seller, optionally about one product
 * (US-INQ-01, §10.5). There is no separate `leads` table — `lead_status`
 * on this row IS the Lead (BR-INQ-01: every inquiry creates exactly one
 * Lead record).
 *
 * `message` is one field beyond the spec's literal column list: an
 * Implementation Assumption so "send inquiry" carries actual content, ready
 * to seed the Conversation Phase 7 attaches to this inquiry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_business_id')->constrained('business_accounts')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('lead_status')->default('new');
            $table->timestamps();

            $table->index(['seller_business_id', 'lead_status']);
            $table->index('buyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
