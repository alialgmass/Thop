<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A product may be offered in several colours — one listing, many colours
 * (US-SEL-01). Pivot table, unique per (product, color) pair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('color_id')->constrained('colors');
            $table->timestamps();

            $table->unique(['product_id', 'color_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colors');
    }
};
