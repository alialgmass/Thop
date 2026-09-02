<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actor columns (created_by / updated_by) on the verification payload rows,
 * recording who uploaded each document and opened each request (DAT-FR-02).
 * Nullable unsignedBigInteger without FK so history survives the actor's
 * account being removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        Schema::table('verification_documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        Schema::table('document_types', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['verification_requests', 'verification_documents', 'document_types'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn(['created_by', 'updated_by']);
            });
        }
    }
};
