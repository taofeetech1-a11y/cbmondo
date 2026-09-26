<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('pu_number');
            $table->text('name');
            $table->string('delimitation_code', 30);
            $table->unsignedInteger('next_member_number')
                ->default(1);
            $table->string('status', 30)->nullable();
            $table->timestamps();

            $table->unique(['ward_id', 'pu_number']);
            $table->index('delimitation_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_units');
    }
};
