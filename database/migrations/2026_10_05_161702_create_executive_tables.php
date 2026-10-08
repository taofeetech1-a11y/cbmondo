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
        Schema::create('executive_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_key', 100);
            $table->string('level', 20);
            $table->unsignedBigInteger('location_key');
            $table->foreignId('lga_id')->nullable()->constrained('lgas')->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards')->restrictOnDelete();
            $table->foreignId('polling_unit_id')->nullable()->constrained('polling_units')->restrictOnDelete();
            $table->unique(['level', 'location_key', 'name_key'], 'executive_position_location_name');
            $table->timestamps();
        });
        Schema::create('executive_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('executive_position_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->constrained('memberships')->cascadeOnDelete();
            $table->string('assignment_group', 10);
            $table->unique(['membership_id', 'assignment_group'], 'executive_member_group');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executive_assignments');
        Schema::dropIfExists('executive_positions');
    }
};
