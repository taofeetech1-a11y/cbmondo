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
        Schema::create('coordinator_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->unique()->constrained('memberships')->cascadeOnDelete();
            $table->string('password');
            $table->string('photo_path')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordinator_agents');
    }
};
