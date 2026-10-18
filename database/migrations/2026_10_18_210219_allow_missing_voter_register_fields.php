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
        Schema::table('voters', function (Blueprint $table) {
            $table->string('vin')->nullable()->change();
            $table->string('full_name')->nullable()->change();
            $table->string('surname')->nullable()->change();
            $table->string('gender', 10)->nullable()->change();
            $table->unsignedTinyInteger('age')->nullable()->change();
            $table->string('register_entry_key', 64)->nullable();
            $table->unique(['polling_unit_id', 'register_entry_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('voters')->whereNull('vin')->orWhereNull('full_name')->orWhereNull('surname')->orWhereNull('gender')->orWhereNull('age')->exists()) {
            throw new RuntimeException('Cannot restore required voter fields while incomplete records exist. Keep this migration and deploy a forward fix.');
        }

        Schema::table('voters', function (Blueprint $table) {
            $table->dropUnique(['polling_unit_id', 'register_entry_key']);
            $table->dropColumn('register_entry_key');
            $table->string('vin')->nullable(false)->change();
            $table->string('full_name')->nullable(false)->change();
            $table->string('surname')->nullable(false)->change();
            $table->string('gender', 10)->nullable(false)->change();
            $table->unsignedTinyInteger('age')->nullable(false)->change();
        });
    }
};
