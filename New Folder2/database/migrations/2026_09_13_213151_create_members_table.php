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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->unique()->nullable();
            $table->string('gender');
            $table->string('age_range');
            $table->string('state')->default('Ondo State');
            $table->string('lga');
            $table->string('ward');
            $table->string('polling_unit');
            $table->string('delimitation_code');
            $table->string('cbm_ond_id');
             $table->enum('support', ['yes','no'])->default('no');
            $table->enum('contact', ['yes','no'])->default('no');
            $table->enum('location_unit', ['yes','no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
