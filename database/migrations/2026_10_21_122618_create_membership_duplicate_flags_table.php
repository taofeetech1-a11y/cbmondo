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
        Schema::create('membership_duplicate_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->constrained()->restrictOnDelete();
            $table->boolean('selection_skipped')->default(false);
            $table->string('status')->default('pending')->index();
            $table->unique(['member_account_id', 'membership_id'], 'member_duplicate_flags_account_membership_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_duplicate_flags');
    }
};
