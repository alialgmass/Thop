<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actor columns (created_by / updated_by) on the controlled reference lists,
 * recording which admin created or last edited a term (DAT-FR-01 / Phase 9).
 * Nullable unsignedBigInteger without FK so history survives the admin's
 * account being removed.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['governorates', 'fabric_types', 'materials', 'units', 'colors'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn(['created_by', 'updated_by']);
            });
        }
    }
};
