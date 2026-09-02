<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('resource');
            $table->string('old_state')->nullable();
            $table->string('new_state')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['resource_type', 'resource_id', 'new_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_logs');
    }
};
