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
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial')->nullable();
            $table->string('vin')->unique();
            $table->string('full_name');
            $table->string('surname');
            $table->string('other_names')->nullable();
            $table->string('occupation')->nullable();
            $table->string('gender', 10);
            $table->unsignedTinyInteger('age');
            $table->string('photo_path')->nullable();
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->string('registration_area')->nullable();
            $table->string('polling_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
