<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controlled reference lists shared across the catalog, search and business
 * profile. Admin-editable management of these lists arrives in Phase 9; this
 * migration only stands up the tables and their read surface.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['governorates', 'fabric_types', 'materials', 'units'] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('hex', 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['governorates', 'fabric_types', 'materials', 'units', 'colors'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
