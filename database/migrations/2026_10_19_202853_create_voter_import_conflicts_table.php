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
        Schema::create('voter_import_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_register_import_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('polling_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('existing_voter_id')->nullable()->constrained('voters')->nullOnDelete();
            $table->foreignId('existing_polling_unit_id')->nullable()->constrained('polling_units')->nullOnDelete();
            $table->string('record_key', 64);
            $table->string('vin')->nullable();
            $table->string('reason');
            $table->json('record');
            $table->string('photo_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['voter_register_import_id', 'record_key'], 'voter_import_conflicts_import_record_unique');
            $table->index(['status', 'id']);
        });
        Schema::table('voter_register_imports', function (Blueprint $table) {
            $table->unsignedInteger('conflicts_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voter_import_conflicts');
        Schema::table('voter_register_imports', function (Blueprint $table) {
            $table->dropColumn('conflicts_count');
        });
    }
};
