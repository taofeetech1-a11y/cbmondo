<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table): void {
            $table->string('phone', 18)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('memberships')->whereRaw('length(phone) > 10')->exists()) {
            throw new RuntimeException('Cannot narrow the phone column: existing phone numbers exceed 10 characters.');
        }

        Schema::table('memberships', function (Blueprint $table): void {
            $table->string('phone', 10)->change();
        });
    }
};
