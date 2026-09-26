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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->string('cbm_id');
            $table->string('name', 30);
            $table->string('phone', 10)->unique();
            $table->string('email', 50)->nullable()->unique();
            $table->string('age_range', 10);
            $table->string('gender', 10);
            $table->string('state', 20)->default('Ondo State');
            $table->string('lga', 50);
            $table->string('ward', 50)->nullable();
            $table->string('pu', 50)->nullable();
            $table->string('delimitation_code')->nullable();
            $table->string('cbm_delimitation_code')->nullable();
            $table->enum('want_to_be_contacted', ['yes', 'no'])->default('yes');
            $table->enum('same_address', ['yes', 'no'])->default('yes')->nullable();
            $table->enum('support_us', ['yes', 'no'])->default('yes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
