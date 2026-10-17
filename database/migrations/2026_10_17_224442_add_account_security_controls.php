<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('session_version')->default(1);
        });
        Schema::table('coordinator_agents', function (Blueprint $table) {
            $table->unsignedInteger('session_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('temporary_password_expires_at')->nullable();
        });
        DB::table('users')->update(['remember_token' => null]);
        DB::table('coordinator_agents')->update(['remember_token' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('session_version'));
        Schema::table('coordinator_agents', fn (Blueprint $table) => $table->dropColumn(['session_version', 'is_active', 'temporary_password_expires_at']));
    }
};
