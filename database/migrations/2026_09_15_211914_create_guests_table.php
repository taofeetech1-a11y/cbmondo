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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();

            $table->string('pass_id')->unique();

            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('lga');
            $table->string('category');
            $table->enum('allow_contact', ['yes', 'no']);
            $table->string('consent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
