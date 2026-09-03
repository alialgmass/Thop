<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product media (US-SEL-03). Images stored on the public S3 disk served via
 * CDN (§12); a product must have at least one image before it may go public.
 * type is an enum with a `video` case reserved for R2 (US-SEL-04) — no video
 * upload path is built in R1. Binary is never stored in MySQL, only metadata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('original_name');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
