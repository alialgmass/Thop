<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actor columns (created_by / updated_by) on the business profile, recording
 * who created and last edited it (DAT-FR-01). Nullable unsignedBigInteger
 * without FK so the profile survives the actor's account being removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_accounts', function (Blueprint $table): void {
            $table->dropColumn(['created_by', 'updated_by']);
        });
    }
};
