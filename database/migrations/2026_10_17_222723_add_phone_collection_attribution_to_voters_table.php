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
        Schema::table('voters', function (Blueprint $table) {
            $table->foreignId('phone_added_by')->nullable()->constrained('coordinator_agents')->nullOnDelete();
            $table->timestamp('phone_added_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('phone_added_by');
            $table->dropColumn('phone_added_at');
        });
    }
};
