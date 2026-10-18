<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voter_register_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('polling_unit_id')->constrained()->restrictOnDelete();
            $table->string('original_name');
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('queued');
            $table->unsignedInteger('voters_count')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['polling_unit_id', 'status']);
            $table->index(['user_id', 'id']);
            $table->index(['status', 'finished_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voter_register_imports');
    }
};
